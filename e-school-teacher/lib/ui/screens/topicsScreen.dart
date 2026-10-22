import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/lessonsCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/cubits/topicsCubit.dart';
import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/data/repositories/topicRepository.dart';
import 'package:eschool_teacher/ui/widgets/classSubjectsDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/defaultDropDownLabelContainer.dart';
import 'package:eschool_teacher/ui/widgets/myClassesDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/topicsContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TopicsScreen extends StatefulWidget {
  const TopicsScreen({super.key});

  static Route<dynamic> route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(create: (context) => LessonsCubit(LessonRepository())),
          BlocProvider(
            create: (context) =>
                SubjectsOfClassSectionCubit(TeacherRepository()),
          ),
          BlocProvider(create: (context) => TopicsCubit(TopicRepository())),
        ],
        child: const TopicsScreen(),
      ),
    );
  }

  @override
  State<TopicsScreen> createState() => _TopicsScreenState();
}

class _TopicsScreenState extends State<TopicsScreen> {
  late CustomDropDownItem currentSelectedClassSection = CustomDropDownItem(
    index: 0,
    title: context.read<DashboardCubit>().getClassSectionName().first,
  );

  late CustomDropDownItem currentSelectedSubject = CustomDropDownItem(
    index: 0,
    title: UiUtils.getTranslatedLabel(context, fetchingSubjectsKey),
  );

  late CustomDropDownItem currentSelectedLesson = CustomDropDownItem(
    index: 0,
    title: UiUtils.getTranslatedLabel(context, fetchingLessonsKey),
  );

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

  Widget _buildClassSubjectAndLessonDropDowns() {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return Column(
          children: [
            MyClassesDropDownMenu(
              currentSelectedItem: currentSelectedClassSection,
              width: boxConstraints.maxWidth,
              changeSelectedItem: (value) {
                currentSelectedClassSection = value;
                context.read<LessonsCubit>().updateState(LessonsInitial());
                context.read<TopicsCubit>().updateState(TopicsInitial());
                setState(() {});
              },
            ),
            ClassSubjectsDropDownMenu(
              changeSelectedItem: (result) {
                setState(() {
                  currentSelectedSubject = result;
                });
                final subject = context
                    .read<SubjectsOfClassSectionCubit>()
                    .getSubjectDetails(currentSelectedSubject.index);
                if (subject.id != -1) {
                  context.read<LessonsCubit>().fetchLessons(
                    classSectionId: context
                        .read<DashboardCubit>()
                        .getClassSectionDetails(
                          index: currentSelectedClassSection.index,
                        )
                        .id,
                    subjectId: subject.id,
                    semesterId: subject.semester?.id,
                  );

                  context.read<TopicsCubit>().updateState(TopicsInitial());
                }
              },
              currentSelectedItem: currentSelectedSubject,
              width: boxConstraints.maxWidth,
            ),

            //
            BlocListener<
              SubjectsOfClassSectionCubit,
              SubjectsOfClassSectionState
            >(
              listener: (context, state) {
                if (state is SubjectsOfClassSectionFetchSuccess) {
                  if (state.subjects.isEmpty) {
                    context.read<LessonsCubit>().updateState(
                      LessonsFetchSuccess([]),
                    );
                    context.read<TopicsCubit>().updateState(
                      TopicsFetchSuccess([]),
                    );
                  }
                }
              },
              child: BlocConsumer<LessonsCubit, LessonsState>(
                builder: (context, state) {
                  return state is LessonsFetchSuccess
                      ? state.lessons.isEmpty
                            ? DefaultDropDownLabelContainer(
                                titleLabelKey: noLessonsKey,
                                width: boxConstraints.maxWidth,
                              )
                            : CustomDropDownMenu(
                                width: boxConstraints.maxWidth,
                                onChanged: (value) {
                                  if (value != null &&
                                      value != currentSelectedLesson) {
                                    currentSelectedLesson = value;
                                    setState(() {});
                                    context.read<TopicsCubit>().fetchTopics(
                                      lessonId: context
                                          .read<LessonsCubit>()
                                          .getLesson(
                                            currentSelectedLesson.index,
                                          )
                                          .id,
                                    );
                                  }
                                },
                                menu: state.lessons.map((e) => e.name).toList(),
                                currentSelectedItem: currentSelectedLesson,
                              )
                      : DefaultDropDownLabelContainer(
                          titleLabelKey: fetchingLessonsKey,
                          width: boxConstraints.maxWidth,
                        );
                },
                listener: (context, state) {
                  if (state is LessonsFetchSuccess) {
                    if (state.lessons.isNotEmpty) {
                      setState(() {
                        currentSelectedLesson = CustomDropDownItem(
                          index: 0,
                          title: state.lessons.first.name,
                        );
                      });
                      context.read<TopicsCubit>().fetchTopics(
                        lessonId: context
                            .read<LessonsCubit>()
                            .getLesson(currentSelectedLesson.index)
                            .id,
                      );
                    }
                  }
                },
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
          Navigator.of(context).pushNamed<bool?>(Routes.addOrEditTopic).then((
            value,
          ) {
            if (value != null && value) {
              if (context.mounted) {
                final lessonsState = context.read<LessonsCubit>().state;
                if (lessonsState is LessonsFetchSuccess &&
                    lessonsState.lessons.isNotEmpty) {
                  context.read<TopicsCubit>().fetchTopics(
                    lessonId: context
                        .read<LessonsCubit>()
                        .getLesson(currentSelectedLesson.index)
                        .id,
                  );
                }
              }
            }
          });
        },
      ),
      body: Stack(
        children: [
          Align(
            alignment: Alignment.topCenter,
            child: CustomRefreshIndicator(
              onRefreshCallback: () {
                context.read<TopicsCubit>().fetchTopics(
                  lessonId: context
                      .read<LessonsCubit>()
                      .getLesson(currentSelectedLesson.index)
                      .id,
                );
              },
              displacement: UiUtils.getScrollViewTopPadding(
                context: context,
                appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
              ),
              child: ListView(
                padding: EdgeInsets.only(
                  left:
                      MediaQuery.sizeOf(context).width *
                      UiUtils.screenContentHorizontalPaddingPercentage,
                  right:
                      MediaQuery.sizeOf(context).width *
                      UiUtils.screenContentHorizontalPaddingPercentage,
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                  ),
                ),
                children: [
                  //
                  _buildClassSubjectAndLessonDropDowns(),
                  SizedBox(
                    height: MediaQuery.sizeOf(context).height * 0.0125,
                  ),

                  BlocBuilder<LessonsCubit, LessonsState>(
                    builder: (context, state) {
                      if (state is LessonsFetchSuccess &&
                          state.lessons.isEmpty) {
                        return const NoDataContainer(titleKey: noTopicsKey);
                      }
                      return TopicsContainer(
                        classSectionDetails: context
                            .read<DashboardCubit>()
                            .getClassSectionDetails(
                              index: currentSelectedClassSection.index,
                            ),
                        subject: context
                            .read<SubjectsOfClassSectionCubit>()
                            .getSubjectDetails(currentSelectedSubject.index),
                        lesson: context.read<LessonsCubit>().getLesson(
                          currentSelectedLesson.index,
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
          Align(
            alignment: Alignment.topCenter,
            child: CustomAppBar(
              title: UiUtils.getTranslatedLabel(context, topicsKey),
            ),
          ),
        ],
      ),
    );
  }
}
