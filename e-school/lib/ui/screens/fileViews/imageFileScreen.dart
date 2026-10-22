import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/customAppbar.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class ImageFileScreen extends StatefulWidget {
  const ImageFileScreen({
    required this.imageFileMaterial,
    required this.listOfImages,
    required this.isFile,
    this.title,
    super.key,
    this.initialPage,
  });
  final StudyMaterial imageFileMaterial;
  final List<StudyMaterial> listOfImages;
  final bool isFile;
  final int? initialPage;
  final String? title;

  static Route route(RouteSettings routeSettings) {
    final arguments =
        routeSettings.arguments as ImageFileViewArguments? ??
        ImageFileViewArguments(studyMaterial: StudyMaterial.fromJson({}));
    final StudyMaterial studyMaterial = arguments.studyMaterial;
    final List<StudyMaterial> multipleFiles = arguments.multiStudyMaterial;
    final bool isItFile = arguments.isFile;
    final int? initialPageNumber = arguments.initialPage;
    final String? title = arguments.title;

    return CupertinoPageRoute(
      builder: (_) => ImageFileScreen(
        imageFileMaterial: studyMaterial,
        listOfImages: multipleFiles,
        isFile: isItFile,
        initialPage: initialPageNumber,
        title: title,
      ),
    );
  }

  @override
  State<ImageFileScreen> createState() => _ImageFileScreenState();
}

class _ImageFileScreenState extends State<ImageFileScreen> {
  late final ValueNotifier<int> _currentImageIndex;
  late final PageController _pageController;
  bool isFullScreen = false;

  @override
  void initState() {
    //goto initial page if specified
    final initialPage = widget.initialPage ?? 0;
    _currentImageIndex = ValueNotifier(initialPage);
    _pageController = PageController(initialPage: initialPage);
    super.initState();
  }

  @override
  void dispose() {
    _pageController.dispose();
    _currentImageIndex.dispose();
    super.dispose();
  }

  Container _fileCountContainer({
    required int currentValue,
    required int totalValues,
  }) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        color: Theme.of(context).colorScheme.scrim.withValues(alpha: 0.38),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      child: Text(
        '$currentValue / $totalValues',
        style: TextStyle(
          color: Theme.of(context).colorScheme.onInverseSurface,
          fontSize: 12,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton(
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
            child: Stack(
              children: [
                PageView(
                  controller: _pageController,
                  onPageChanged: (value) {
                    _currentImageIndex.value = value;
                  },
                  children:
                      (widget.listOfImages.isEmpty
                              ? [widget.imageFileMaterial]
                              : widget.listOfImages)
                          .map(
                            (e) => InteractiveViewer(
                              minScale: 0.5,
                              maxScale: 4,
                              child: CustomImageWidget(
                                imagePath: e.fileUrl,
                                isFile: widget.isFile,
                                boxFit: BoxFit.contain,
                                errorWidget: (context, url, error) => Center(
                                  child: ErrorContainer(
                                    errorMessageText:
                                        UiUtils.getTranslatedLabel(
                                          context,
                                          imageFileOpenErrorKey,
                                        ),
                                  ),
                                ),
                                progressIndicatorBuilder: (context, url, progress) => Padding(
                                  padding: EdgeInsets.all(
                                    MediaQuery.sizeOf(context).width * 0.1,
                                  ),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        UiUtils.getTranslatedLabel(
                                          context,
                                          imageFileLoadingKey,
                                        ),
                                        style: TextStyle(
                                          fontSize: 16,
                                          color: Theme.of(
                                            context,
                                          ).colorScheme.onSurface,
                                        ),
                                      ),
                                      const SizedBox(height: 10),
                                      Text(
                                        '${((progress.downloaded / (progress.totalSize ?? 100)) * 100).toStringAsFixed(2)} %',
                                        style: TextStyle(
                                          fontSize: 16,
                                          color: Theme.of(
                                            context,
                                          ).colorScheme.primary,
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
                                                  width:
                                                      boxConstraints.maxWidth,
                                                  color: Theme.of(context)
                                                      .colorScheme
                                                      .onSurfaceVariant
                                                      .withValues(alpha: 0.5),
                                                ),
                                                UiUtils.buildProgressContainer(
                                                  width:
                                                      boxConstraints.maxWidth *
                                                      (progress.downloaded /
                                                          (progress.totalSize ??
                                                              100)) *
                                                      100 *
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
                                ),
                              ),
                            ),
                          )
                          .toList(),
                ),
                Positioned(
                  top: 30,
                  right: 15,
                  child: ValueListenableBuilder<int>(
                    valueListenable: _currentImageIndex,
                    builder: (context, value, child) =>
                        widget.listOfImages.isEmpty
                        ? const SizedBox.shrink()
                        : _fileCountContainer(
                            currentValue: value + 1,
                            totalValues: widget.listOfImages.length,
                          ),
                  ),
                ),
              ],
            ),
          ),
          if (!isFullScreen)
            ValueListenableBuilder<int>(
              valueListenable: _currentImageIndex,
              builder: (context, value, child) {
                final StudyMaterial current = widget.listOfImages.isEmpty
                    ? widget.imageFileMaterial
                    : widget.listOfImages[value];
                return CustomAppBar(
                  title:
                      widget.title ??
                      current.fileName.substring(
                        0,
                        current.fileName.lastIndexOf('.') == -1
                            ? current.fileName.length
                            : current.fileName.lastIndexOf('.'),
                      ),
                  actionButton: widget.isFile
                      ? null
                      : IconButton(
                          icon: SvgPicture.asset(
                            Assets.downloadIcon,
                            width: 20,
                            height: 20,
                          ),
                          onPressed: () {
                            UiUtils.openDownloadBottomsheet(
                              context: context,
                              studyMaterial: current,
                            );
                          },
                        ),
                );
              },
            ),
        ],
      ),
    );
  }
}
