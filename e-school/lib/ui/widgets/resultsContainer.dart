import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/resultTabSelectionCubit.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/resultsOnlineCubit.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/resultOnline.dart';
import 'package:eschool/data/models/semester.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/widgets/assignmentsSubjectsContainer.dart';
import 'package:eschool/ui/widgets/customTabBarContainer.dart';
import 'package:eschool/ui/widgets/listItemForExamAndResult.dart';
import 'package:eschool/ui/widgets/listItemForOnlineExamAndOnlineResult.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/semesterFilterBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ResultsContainer extends StatefulWidget {
  const ResultsContainer({super.key, this.childId, this.subjects});
  final int? childId;
  final List<Subject>? subjects;

  @override
  State<ResultsContainer> createState() => _ResultsContainerState();
}

class _ResultsContainerState extends State<ResultsContainer> {
  late ValueNotifier<Semester?> selectedSemesterNotifier =
      ValueNotifier<Semester?>(
        context
            .read<AppConfigurationCubit>()
            .getAppConfiguration()
            .currentSemester,
      );

  bool get doesChildHaveSemesterInClass {
    if (widget.childId == null) {
      return context.read<AuthCubit>().getStudentDetails().isClassSemesterOn;
    } else {
      return context
          .read<AuthCubit>()
          .getParentDetails()
          .getChild(widget.childId!)
          .isClassSemesterOn;
    }
  }

  late final ScrollController _scrollController = ScrollController()
    ..addListener(_resultsScrollListener);
  //same for both Online and Offline Result

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      context.read<SemestersCubit>().fetchSemesters();
      fetchResults();
      fetchOnlineResults();
    });
    selectedSemesterNotifier.addListener(() {
      fetchResults();
    });
    super.initState();
  }

  void fetchResults() {
    context.read<ResultsCubit>().fetchResults(
      useParentApi: context.read<AuthCubit>().isParent(),
      childId: widget.childId,
      semesterId: selectedSemesterNotifier.value?.id,
    );
  }

  void _resultsScrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<ResultTabSelectionCubit>().isResultOnline()) {
        if (context.read<ResultsOnlineCubit>().hasMore()) {
          context.read<ResultsOnlineCubit>().fetchMoreResultsOnline(
            useParentApi: context.read<AuthCubit>().isParent(),
            childId: widget.childId ?? 0,
            subjectId: context
                .read<ResultTabSelectionCubit>()
                .state
                .resultFilterBySubjectId,
          );
          //to scroll to last in order for users to see the progress
          Future.delayed(const Duration(milliseconds: 10), () {
            _scrollController.animateTo(
              _scrollController.position.maxScrollExtent,
              duration: const Duration(milliseconds: 500),
              curve: Curves.easeIn,
            );
          });
        }
      } else {
        if (context.read<ResultsCubit>().hasMore()) {
          context.read<ResultsCubit>().fetchMoreResults(
            useParentApi: context.read<AuthCubit>().isParent(),
            childId: widget.childId,
            semesterId: selectedSemesterNotifier.value?.id,
          );
          //to scroll to last in order for users to see the progress
          Future.delayed(const Duration(milliseconds: 10), () {
            _scrollController.animateTo(
              _scrollController.position.maxScrollExtent,
              duration: const Duration(milliseconds: 500),
              curve: Curves.easeIn,
            );
          });
        }
      }
    }
  }

  @override
  void dispose() {
    _scrollController.removeListener(_resultsScrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  Widget _buildAppBar(ResultTabSelectionState currentState) {
    return ScreenTopBackgroundContainer(
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              context.read<AuthCubit>().isParent()
                  ? const CustomBackButton()
                  : const SizedBox.shrink(),
              currentState.resultFilterTabTitle == offlineKey &&
                      doesChildHaveSemesterInClass &&
                      selectedSemesterNotifier.value != null
                  ? Align(
                      alignment: AlignmentDirectional.topEnd,
                      child: Padding(
                        padding: EdgeInsetsDirectional.only(
                          end: UiUtils.screenContentHorizontalPadding,
                        ),
                        child: SvgButton(
                          onTap: () async {
                            final Semester? newSelection =
                                await showModalBottomSheet(
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.only(
                                      topLeft: Radius.circular(
                                        UiUtils.bottomSheetTopRadius,
                                      ),
                                      topRight: Radius.circular(
                                        UiUtils.bottomSheetTopRadius,
                                      ),
                                    ),
                                  ),
                                  context: context,
                                  builder: (_) => BlocProvider.value(
                                    value: context.read<SemestersCubit>(),
                                    child: SemesterFilterBottomsheetContainer(
                                      selectedSemester:
                                          selectedSemesterNotifier.value!,
                                    ),
                                  ),
                                );
                            if (newSelection != null) {
                              selectedSemesterNotifier.value = newSelection;
                            }
                          },
                          svgIconUrl: Assets.filterIcon,
                        ),
                      ),
                    )
                  : const SizedBox(),
              Align(
                alignment: Alignment.topCenter,
                child: Text(
                  UiUtils.getTranslatedLabel(context, resultsKey),
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onInverseSurface,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ),
              AnimatedAlign(
                curve: UiUtils.tabBackgroundContainerAnimationCurve,
                duration: UiUtils.tabBackgroundContainerAnimationDuration,
                alignment: currentState.resultFilterTabTitle == offlineKey
                    ? AlignmentDirectional.centerStart
                    : AlignmentDirectional.centerEnd,
                child: TabBarBackgroundContainer(
                  boxConstraints: boxConstraints,
                ),
              ),
              CustomTabBarContainer(
                boxConstraints: boxConstraints,
                alignment: AlignmentDirectional.centerStart,
                isSelected: currentState.resultFilterTabTitle == offlineKey,
                onTap: () {
                  context
                      .read<ResultTabSelectionCubit>()
                      .changeResultFilterTabTitle(offlineKey);
                },
                titleKey: offlineKey,
              ),
              CustomTabBarContainer(
                boxConstraints: boxConstraints,
                alignment: AlignmentDirectional.centerEnd,
                isSelected: currentState.resultFilterTabTitle == onlineKey,
                onTap: () {
                  context
                      .read<ResultTabSelectionCubit>()
                      .changeResultFilterTabTitle(onlineKey);
                },
                titleKey: onlineKey,
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildResultDetailsShimmerLoadingContainer() {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12.5),
      width: MediaQuery.sizeOf(context).width * 0.85,
      height: 80,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  width: boxConstraints.maxWidth * 0.7,
                ),
              ),
              SizedBox(height: boxConstraints.maxHeight * 0.25),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  width: boxConstraints.maxWidth * 0.5,
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildResultDetailsContainer({
    required Result result,
    required int index,
    required int totalResults,
    required bool hasMoreResults,
    required bool hasMoreResultsInProgress,
    required bool fetchMoreResultsFailure,
  }) {
    return Column(
      children: [
        ListItemForExamAndResult(
          index: index,
          examStartingDate: result.examDate,
          examName: result.examName,
          resultGrade: result.grade,
          resultPercentage: result.percentage,
          onItemTap: () {
            Navigator.of(context).pushNamed(
              Routes.result,
              arguments: {'childId': widget.childId, 'result': result},
            );
          },
        ),
        if (index == (totalResults - 1) &&
            hasMoreResults &&
            hasMoreResultsInProgress)
          _buildResultDetailsShimmerLoadingContainer(),
        if (index == (totalResults - 1) &&
            hasMoreResults &&
            fetchMoreResultsFailure)
          Center(
            child: TextButton(
              child: Text(UiUtils.getTranslatedLabel(context, retryKey)),
              onPressed: () {
                context.read<ResultsCubit>().fetchMoreResults(
                  useParentApi: context.read<AuthCubit>().isParent(),
                  childId: widget.childId,
                  semesterId: selectedSemesterNotifier.value?.id,
                );
              },
            ),
          ),
      ],
    );
  }

  Widget buildOfflineResults() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomRefreshIndicator(
        onRefreshCallback: () {
          if (context.read<ResultsCubit>().state is ResultsFetchSuccess) {
            fetchResults();
          }
        },
        displacement: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
        ),
        child: SingleChildScrollView(
          controller: _scrollController,
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.only(
            bottom: UiUtils.getScrollViewBottomPadding(context),
            top: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
            ),
          ),
          child: BlocBuilder<ResultsCubit, ResultsState>(
            builder: (context, state) {
              if (state is ResultsFetchSuccess) {
                return state.results.isNotEmpty
                    ? Column(
                        children:
                            List.generate(
                              state.results.length,
                              (index) => index,
                            ).map((index) {
                              return _buildResultDetailsContainer(
                                result: state.results[index],
                                index: index,
                                totalResults: state.results.length,
                                hasMoreResults: context
                                    .read<ResultsCubit>()
                                    .hasMore(),
                                hasMoreResultsInProgress:
                                    state.fetchMoreResultsInProgress,
                                fetchMoreResultsFailure:
                                    state.moreResultsFetchError,
                              );
                            }).toList(),
                      )
                    : const Center(
                        child: NoDataContainer(titleKey: noResultPublishedKey),
                      );
              }
              if (state is ResultsFetchFailure) {
                return ErrorContainer(
                  errorMessageCode: state.errorMessage,
                  onTapRetry: () {
                    fetchResults();
                  },
                );
              }
              return Column(
                children: List.generate(
                  UiUtils.defaultShimmerLoadingContentCount,
                  (index) => _buildResultDetailsShimmerLoadingContainer(),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  //Online Result
  Widget buildMySubjectsListContainer() {
    return BlocBuilder<StudentDashboardCubit, StudentDashboardState>(
      builder: (context, state) {
        return BlocBuilder<ResultTabSelectionCubit, ResultTabSelectionState>(
          bloc: context.read<ResultTabSelectionCubit>(),
          builder: (context, state) {
            return AssignmentsSubjectContainer(
              cubitAndState: 'onlineResult',
              onTapSubject: (subjectId) {
                //fetch student online Result according to respected subjectId
                context
                    .read<ResultTabSelectionCubit>()
                    .changeResultFilterBySubjectId(subjectId);
                fetchOnlineResults();
              },
              selectedSubjectId: state.resultFilterBySubjectId,
              subjects: (widget.subjects != null)
                  ? widget.subjects! //from parent
                  : context
                        .read<StudentDashboardCubit>()
                        .getSubjectsForAssignmentContainer(),
            );
          },
        );
      },
    );
  }

  void fetchOnlineResults() {
    context.read<ResultsOnlineCubit>().fetchResultsOnline(
      useParentApi: context.read<AuthCubit>().isParent(),
      childId: widget.childId ?? 0,
      subjectId: context
          .read<ResultTabSelectionCubit>()
          .state
          .resultFilterBySubjectId,
    );
  }

  Widget _buildOnlineResultDetailsContainer({
    required ResultOnline result,
    required int index,
    required int totalResults,
    required bool hasMoreResults,
    required bool hasMoreResultsInProgress,
    required bool fetchMoreResultsFailure,
  }) {
    if (index == (totalResults - 1)) {
      if (hasMoreResults) {
        if (hasMoreResultsInProgress) {
          return _buildResultDetailsShimmerLoadingContainer(); //same for both Online and Offline Result
        }
        if (fetchMoreResultsFailure) {
          return Center(
            child: TextButton(
              child: Text(UiUtils.getTranslatedLabel(context, retryKey)),
              onPressed: () {
                context.read<ResultsOnlineCubit>().fetchMoreResultsOnline(
                  useParentApi: context.read<AuthCubit>().isParent(),
                  childId: widget.childId ?? 0,
                  subjectId: context
                      .read<ResultTabSelectionCubit>()
                      .state
                      .resultFilterBySubjectId,
                );
              },
            ),
          );
        }
      }
    }
    return ListItemForOnlineExamAndOnlineResult(
      examStartingDate: result.examDate,
      examEndingDate: result.examDate,
      examName: result.examName,
      subjectName: result.subject.showType
          ? result.subject.subjectNameWithType
          : result.subject.name,
      totalMarks: result.totalMarks,
      marks: result.obtainedMarks,
      isSubjectSelected:
          (context
                  .read<ResultTabSelectionCubit>()
                  .state
                  .resultFilterBySubjectId !=
              0)
          ? true
          : false,
      onItemTap: () {
        Navigator.of(context).pushNamed(
          Routes.resultOnline,
          arguments: {
            'examId': result.examId,
            'examName': result.examName,
            'subjectName': result.subject.showType
                ? result.subject.subjectNameWithType
                : result.subject.name,
            'childId': widget.childId,
            'totalMarks': result.totalMarks,
            'obtainedMarks': result.obtainedMarks,
          },
        );
      },
    );
  }

  Widget buildOnlineResults() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomRefreshIndicator(
        onRefreshCallback: () {
          if (context.read<ResultsOnlineCubit>().state
              is ResultsOnlineFetchSuccess) {
            fetchOnlineResults();
          }
        },
        displacement: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
        ),
        child: SingleChildScrollView(
          controller: _scrollController,
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.only(
            bottom: UiUtils.getScrollViewBottomPadding(context),
            top: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
            ),
          ),
          child: Column(
            children: [
              buildMySubjectsListContainer(),
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.035),
              BlocBuilder<ResultsOnlineCubit, ResultsOnlineState>(
                builder: (context, state) {
                  if (state is ResultsOnlineFetchSuccess) {
                    return state.results.isNotEmpty
                        ? Column(
                            children:
                                List.generate(
                                  state.results.length,
                                  (index) => index,
                                ).map((index) {
                                  return _buildOnlineResultDetailsContainer(
                                    result: state.results[index],
                                    index: index,
                                    totalResults: state.results.length,
                                    hasMoreResults: context
                                        .read<ResultsOnlineCubit>()
                                        .hasMore(),
                                    hasMoreResultsInProgress:
                                        state.fetchMoreResultsOnlineInProgress,
                                    fetchMoreResultsFailure:
                                        state.moreResultsOnlineFetchError,
                                  );
                                }).toList(),
                          )
                        : const Center(
                            child: NoDataContainer(
                              titleKey: noResultPublishedKey,
                            ),
                          );
                  }
                  if (state is ResultsOnlineFetchFailure) {
                    return ErrorContainer(
                      errorMessageCode: state.errorMessage,
                      onTapRetry: () {
                        fetchOnlineResults();
                      },
                    );
                  }
                  return Column(
                    children: List.generate(
                      UiUtils.defaultShimmerLoadingContentCount,
                      (index) => _buildResultDetailsShimmerLoadingContainer(),
                    ),
                    //same for both Online and Offline Result
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ResultTabSelectionCubit, ResultTabSelectionState>(
      builder: (context, state) {
        return Stack(
          children: [
            (context.read<ResultTabSelectionCubit>().isResultOnline())
                ? buildOnlineResults()
                : buildOfflineResults(),
            Align(alignment: Alignment.topCenter, child: _buildAppBar(state)),
          ],
        );
      },
    );
  }
}
