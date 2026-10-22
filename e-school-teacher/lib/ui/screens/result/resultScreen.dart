import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_teacher/cubits/downloadResultPdfCubit.dart';
import 'package:eschool_teacher/cubits/examCubit.dart';
import 'package:eschool_teacher/cubits/studentCompletedExamWithResultCubit.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/ui/screens/result/widget/resultsContainer.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class ResultListScreen extends StatelessWidget {
  const ResultListScreen({
    required this.classSectionId,
    super.key,
    this.studentId,
    this.studentName,
    this.className,
  });
  final int? studentId;
  final String? studentName;
  final String? className;
  final int classSectionId;

  static Route route(RouteSettings routeSettings) {
    final studentData = routeSettings.arguments! as ResultListArguments;
    return AdaptivePageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<ExamDetailsCubit>(
            create: (context) => ExamDetailsCubit(StudentRepository()),
          ),
          BlocProvider<StudentCompletedExamWithResultCubit>(
            create: (context) =>
                StudentCompletedExamWithResultCubit(StudentRepository()),
          ),
          BlocProvider(
            create: (context) => DownloadResultPdfCubit(StudentRepository()),
          ),
        ],
        child: ResultListScreen(
          studentId: studentData.studentId,
          studentName: studentData.studentName,
          className: studentData.className,
          classSectionId: studentData.classSectionId,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          ResultsContainer(
            studentId: studentId,
            studentName: studentName,
            className: className,
            classSectionId: classSectionId,
          ),
          Align(
            alignment: Alignment.topCenter,
            child: CustomAppBar(
              title: UiUtils.getTranslatedLabel(context, resultKey),
              subTitle: studentName,
              showBackButton: true,
              onPressBackButton: () {
                Navigator.pop(context);
              },
              trailingWidget:
                  BlocBuilder<
                    StudentCompletedExamWithResultCubit,
                    StudentCompletedExamWithResultState
                  >(
                    builder: (context, state) {
                      if (state is StudentCompletedExamWithResultFetchSuccess) {
                        //if any exam is result published we let them download the result
                        if (state.studentCompletedExamWithResultList.any(
                          (element) => element.result?.resultId != 0,
                        )) {
                          return BlocConsumer<
                            DownloadResultPdfCubit,
                            DownloadResultPdfState
                          >(
                            listener: (context, state) async {
                              if (state is DownloadResultPdfDownloadFailure) {
                                UiUtils.showBottomToastOverlay(
                                  context: context,
                                  errorMessage: UiUtils.getTranslatedLabel(
                                    context,
                                    failedToDownloadKey,
                                  ),
                                  backgroundColor: Theme.of(
                                    context,
                                  ).colorScheme.error,
                                );
                              } else if (state
                                  is DownloadResultPdfDownloadSuccess) {
                                final bool opened = await DeviceFiles.open(
                                  state.filePath,
                                );
                                if (!opened) {
                                  if (context.mounted) {
                                    UiUtils.showBottomToastOverlay(
                                      context: context,
                                      errorMessage: UiUtils.getTranslatedLabel(
                                        context,
                                        unableToOpenKey,
                                      ),
                                      backgroundColor: Theme.of(
                                        context,
                                      ).colorScheme.error,
                                    );
                                  }
                                }
                              }
                            },
                            builder: (context, state) {
                              if (state
                                  is DownloadResultPdfDownloadInProgress) {
                                return SizedBox(
                                  height: 25,
                                  width: 25,
                                  child: CustomCircularProgressIndicator(
                                    strokeWidth: 2,
                                    widthAndHeight: 20,
                                    indicatorColor: Theme.of(
                                      context,
                                    ).colorScheme.onInverseSurface,
                                  ),
                                );
                              } else {
                                return GestureDetector(
                                  onTap: () {
                                    context
                                        .read<DownloadResultPdfCubit>()
                                        .downloadDownloadResultPdf(
                                          studentId: studentId ?? 0,
                                          fileNamePrefix:
                                              '${studentName}_${UiUtils.getTranslatedLabel(context, resultKey)}',
                                        );
                                  },
                                  child: SvgPicture.asset(
                                    Assets.downloadIcon,
                                    height: 25,
                                    width: 25,
                                  ),
                                );
                              }
                            },
                          );
                        }
                      }
                      return const SizedBox.shrink();
                    },
                  ),
            ),
          ),
        ],
      ),
    );
  }
}
