import 'package:eschool_teacher/cubits/pdfFileCubit.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/data/repositories/studyMaterialRepositoy.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
import 'package:flutter_svg/svg.dart';
import 'package:open_filex/open_filex.dart';

class PdfFileScreen extends StatefulWidget {
  const PdfFileScreen({
    required this.pdfFileMaterial,
    super.key,
    this.isFile = false,
  });
  final StudyMaterial pdfFileMaterial;
  final bool isFile;

  static Route route(RouteSettings routeSettings) {
    final StudyMaterial studyMaterial =
        (routeSettings.arguments as Map?)?['studyMaterial'] ??
        StudyMaterial.fromJson({});
    final bool isItFile = (routeSettings.arguments as Map?)?['isFile'] ?? false;
    return CupertinoPageRoute(
      builder: (_) => BlocProvider(
        create: (context) => PdfFileSaveCubit(StudyMaterialRepository()),
        child: PdfFileScreen(pdfFileMaterial: studyMaterial, isFile: isItFile),
      ),
    );
  }

  @override
  State<PdfFileScreen> createState() => _PdfFileScreenState();
}

class _PdfFileScreenState extends State<PdfFileScreen> {
  bool isFullScreen = false;
  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<PdfFileSaveCubit>().savePdfFile(
          studyMaterial: widget.pdfFileMaterial,
          storeInExternalStorage: false,
          isFile: widget.isFile,
        );
      }
    });
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: BlocBuilder<PdfFileSaveCubit, PdfFileSaveState>(
        builder: (context, state) {
          if (state is PdfFileSaveSuccess) {
            return FloatingActionButton(
              backgroundColor: Theme.of(context).colorScheme.primary,
              onPressed: () {
                setState(() {
                  isFullScreen = !isFullScreen;
                });
              },
              child: Icon(
                isFullScreen ? Icons.fullscreen_exit : Icons.fullscreen,
                color: Theme.of(context).colorScheme.onPrimary,
              ),
            );
          }
          return const SizedBox.shrink();
        },
      ),
      body: Stack(
        children: [
          Padding(
            padding: EdgeInsets.only(
              top: !isFullScreen
                  ? UiUtils.getScrollViewTopPadding(
                      context: context,
                      appBarHeightPercentage:
                          UiUtils.appBarSmallerHeightPercentage,
                      keepExtraSpace: false,
                    )
                  : 0,
            ),
            child: BlocBuilder<PdfFileSaveCubit, PdfFileSaveState>(
              builder: (context, state) {
                if (state is PdfFileSaveInProgress) {
                  return Padding(
                    padding: EdgeInsets.all(
                      MediaQuery.sizeOf(context).width * 0.1,
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          UiUtils.getTranslatedLabel(
                            context,
                            pdfFileLoadingKey,
                          ),
                          style: TextStyle(
                            fontSize: 16,
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          '${state.uploadedPercentage.toStringAsFixed(2)} %',
                          style: TextStyle(
                            fontSize: 16,
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          height: 6,
                          child: LayoutBuilder(
                            builder: (context, boxConstraints) {
                              return Stack(
                                children: [
                                  UiUtils.buildProgressContainer(
                                    width: boxConstraints.maxWidth,
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurfaceVariant
                                        .withValues(alpha: 0.5),
                                  ),
                                  UiUtils.buildProgressContainer(
                                    width:
                                        boxConstraints.maxWidth *
                                        state.uploadedPercentage *
                                        0.01,
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.primary,
                                  ),
                                ],
                              );
                            },
                          ),
                        ),
                      ],
                    ),
                  );
                } else if (state is PdfFileSaveSuccess) {
                  return PDFView(filePath: state.pdfFilePath, pageFling: false);
                } else {
                  return Center(
                    child: ErrorContainer(
                      errorMessageText: UiUtils.getTranslatedLabel(
                        context,
                        pdfFileOpenErrorKey,
                      ),
                    ),
                  );
                }
              },
            ),
          ),
          if (!isFullScreen)
            CustomAppBar(
              title: widget.pdfFileMaterial.fileName.replaceFirst(
                '.${widget.pdfFileMaterial.fileExtension}',
                '',
              ),
              actionButton: widget.isFile
                  ? IconButton(
                      icon: Icon(
                        Icons.open_in_new_outlined,
                        size: 20,
                        color: Theme.of(context).colorScheme.onPrimary,
                      ),
                      onPressed: () {
                        OpenFilex.open(widget.pdfFileMaterial.fileUrl);
                      },
                    )
                  : IconButton(
                      icon: SvgPicture.asset(
                        Assets.downloadIcon,
                        width: 20,
                        height: 20,
                      ),
                      onPressed: () {
                        UiUtils.openDownloadBottomsheet(
                          context: context,
                          studyMaterial: widget.pdfFileMaterial,
                        );
                      },
                    ),
            ),
        ],
      ),
    );
  }
}
