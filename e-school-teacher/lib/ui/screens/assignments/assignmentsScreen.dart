import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/assignmentCubit.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/assignments/widgets/assignmentsContainer.dart';
import 'package:eschool_teacher/ui/widgets/classSubjectsDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/myClassesDropDownMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AssignmentsScreen extends StatefulWidget {
  const AssignmentsScreen({super.key});

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<SubjectsOfClassSectionCubit>(
            create: (context) =>
                SubjectsOfClassSectionCubit(TeacherRepository()),
          ),
          BlocProvider<AssignmentCubit>(
            create: (context) => AssignmentCubit(AssignmentRepository()),
          ),
        ],
        child: const AssignmentsScreen(),
      ),
    );
  }

  @override
  State<AssignmentsScreen> createState() => _AssignmentsScreenState();
}

class _AssignmentsScreenState extends State<AssignmentsScreen> {
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_announcementsScrollListener);

  void _announcementsScrollListener() {
    if (_scrollController.offset ==
        _scrollController.position.maxScrollExtent) {
      if (context.read<AssignmentCubit>().hasMore()) {
        final subject = context
            .read<SubjectsOfClassSectionCubit>()
            .getSubjectDetails(currentSelectedSubject.index);
        context.read<AssignmentCubit>().fetchMoreAssignment(
          subjectId: subject.id,
          semesterId: subject.semester?.id,
          classSectionId: context
              .read<DashboardCubit>()
              .getClassSectionDetails(index: currentSelectedClassSection.index)
              .id,
        );
      }
    }
  }

  void fetchAssignment() {
    final subjectState = context.read<SubjectsOfClassSectionCubit>().state;

    if (subjectState is SubjectsOfClassSectionFetchSuccess &&
        subjectState.subjects.isNotEmpty) {
      final subject = context
          .read<SubjectsOfClassSectionCubit>()
          .getSubjectDetails(currentSelectedSubject.index);
      if (subject.id != -1) {
        context.read<AssignmentCubit>().fetchAssignment(
          classSectionId: context
              .read<DashboardCubit>()
              .getClassSectionDetails(index: currentSelectedClassSection.index)
              .id,
          subjectId: subject.id,
          semesterId: subject.semester?.id,
        );
      }
    }
  }

  @override
  void initState() {
    context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
      context
          .read<DashboardCubit>()
          .getClassSectionDetails(index: currentSelectedClassSection.index)
          .id,
    );
    super.initState();
  }

  late CustomDropDownItem currentSelectedClassSection = CustomDropDownItem(
    index: 0,
    title: context.read<DashboardCubit>().getClassSectionName().first,
  );

  late CustomDropDownItem currentSelectedSubject = CustomDropDownItem(
    index: 0,
    title: UiUtils.getTranslatedLabel(context, fetchingSubjectsKey),
  );

  Widget _buildAppbar() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        title: UiUtils.getTranslatedLabel(context, assignmentsKey),
      ),
    );
  }

  Widget _buildAssignmentFilters() {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return Column(
          children: [
            MyClassesDropDownMenu(
              currentSelectedItem: currentSelectedClassSection,
              width: boxConstraints.maxWidth,
              changeSelectedItem: (result) {
                setState(() {
                  currentSelectedClassSection = result;
                });

                context.read<AssignmentCubit>().updateState(
                  AssignmentInitial(),
                );
              },
            ),

            //
            BlocListener<
              SubjectsOfClassSectionCubit,
              SubjectsOfClassSectionState
            >(
              listener: (context, state) {
                if (state is SubjectsOfClassSectionFetchSuccess) {
                  if (state.subjects.isEmpty) {
                    context.read<AssignmentCubit>().updateState(
                      AssignmentsFetchSuccess(
                        assignment: [],
                        fetchMoreAssignmentsInProgress: false,
                        moreAssignmentsFetchError: false,
                        totalPage: 0,
                        currentPage: 0,
                      ),
                    );
                  }
                }
              },
              child: ClassSubjectsDropDownMenu(
                changeSelectedItem: (result) {
                  setState(() {
                    currentSelectedSubject = result;
                  });
                  fetchAssignment();
                },
                currentSelectedItem: currentSelectedSubject,
                width: boxConstraints.maxWidth,
              ),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionAddButton(
        onTap: () {
          Navigator.of(context)
              .pushNamed<bool?>(
                Routes.addAssignment,
                arguments: {'editAssignment': false},
              )
              .then((value) {
                if (value != null && value) {
                  fetchAssignment();
                }
              });
        },
      ),
      body: Stack(
        children: [
          CustomRefreshIndicator(
            displacement: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
            ),
            onRefreshCallback: () {
              fetchAssignment();
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              controller: _scrollController,
              padding: EdgeInsets.only(
                left:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingPercentage,
                right:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingPercentage,
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
                ),
              ),
              children: [
                _buildAssignmentFilters(),
                const SizedBox(height: 10),
                AssignmentsContainer(
                  classSectionDetails: context
                      .read<DashboardCubit>()
                      .getClassSectionDetails(
                        index: currentSelectedClassSection.index,
                      ),
                  subject:
                      (context.read<SubjectsOfClassSectionCubit>().state
                              is SubjectsOfClassSectionFetchSuccess &&
                          (context.read<SubjectsOfClassSectionCubit>().state
                                  as SubjectsOfClassSectionFetchSuccess)
                              .subjects
                              .isNotEmpty)
                      ? context
                            .read<SubjectsOfClassSectionCubit>()
                            .getSubjectDetails(currentSelectedSubject.index)
                      : Subject.fromJson({}),
                ),
              ],
            ),
          ),
          _buildAppbar(),
        ],
      ),
    );
  }
}
