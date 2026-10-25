import 'package:eschool_teacher/app/sharedScreens.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/timeTableCubit.dart';
import 'package:eschool_teacher/cubits/updateStudentLeaveStatusCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/data/repositories/studentLeaveRepository.dart';
import 'package:eschool_teacher/ui/screens/academicCalendar/widgets/listItemForEvents.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/classTeacherClassContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerAppBarContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerExamItemContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerExploreAcademicsItemContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerShimmerContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerStaffLeavesContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerStudentLeaveRequestContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerTodaysTimetableContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/subjectTeacherClassContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/fixHeightDeligate.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class HomeContainer extends StatefulWidget {
  const HomeContainer({super.key});

  @override
  State<HomeContainer> createState() => _HomeContainerState();
}

class _HomeContainerState extends State<HomeContainer> {
  List<MenuContainerDetails> get exploreAcademicsItems => [
    if (allowsSchoolModule(
      context.watch<MeContextCubit>().context,
      'discipline',
    ))
      MenuContainerDetails(
        route: Routes.schoolDiscipline,
        iconPath: Assets.resultIcon,
        titleKey: 'School discipline',
      ),
    if (context.watch<MeContextCubit>().context?.isModuleOn('early_warning') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'early_warning',
        ))
      MenuContainerDetails(
        route: Routes.riskInterventions,
        iconPath: Assets.resultIcon,
        titleKey: 'Staff follow-up',
      ),
    if (allowsSchoolModule(
      context.watch<MeContextCubit>().context,
      'academics',
    ))
      MenuContainerDetails(
        route: Routes.progressNotes,
        iconPath: Assets.resultIcon,
        titleKey: 'Progress and notes',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn('mastery') ==
            true &&
        allowsSchoolModule(context.watch<MeContextCubit>().context, 'mastery'))
      MenuContainerDetails(
        route: Routes.mastery,
        iconPath: Assets.resultIcon,
        titleKey: 'Mastery map',
      ),
    if (context.watch<MeContextCubit>().context?.roles.any(
              (role) => ['Counsellor', 'Safeguarding'].contains(role),
            ) ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'wellbeing',
        ))
      MenuContainerDetails(
        route: Routes.wellbeing,
        iconPath: Assets.announcementIcon,
        titleKey: 'Incidents and counselling',
      ),
    if (context.watch<MeContextCubit>().context?.isModuleOn('support_plans') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'support_plans',
        ))
      MenuContainerDetails(
        route: Routes.supportPlans,
        iconPath: Assets.announcementIcon,
        titleKey: 'Support plans',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn('relief_cover') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'relief_cover',
        ))
      MenuContainerDetails(
        route: Routes.reliefCover,
        iconPath: Assets.announcementIcon,
        titleKey: 'Relief cover',
      ),
    if (allowsSchoolModule(context.watch<MeContextCubit>().context, 'groups'))
      MenuContainerDetails(
        route: Routes.activityGroups,
        iconPath: Assets.announcementIcon,
        titleKey: 'Activity groups',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
              'communication_messages',
            ) ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'communication',
        ))
      MenuContainerDetails(
        route: Routes.schoolMessages,
        iconPath: Assets.announcementIcon,
        titleKey: 'School messages',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_content',
        ) ==
        true)
      MenuContainerDetails(
        route: Routes.academicContent,
        iconPath: Assets.assignmentIcon,
        titleKey: 'Learning content',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_reports',
        ) ==
        true)
      MenuContainerDetails(
        route: Routes.academicReports,
        iconPath: Assets.resultIcon,
        titleKey: 'reportCards',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_registers',
        ) ==
        true)
      MenuContainerDetails(
        route: Routes.academicRegisters,
        iconPath: Assets.attendanceIcon,
        titleKey: 'academicRegisters',
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_marks',
        ) ==
        true)
      MenuContainerDetails(
        route: Routes.academicMarks,
        iconPath: Assets.resultIcon,
        titleKey: 'assessmentMarks',
      ),
    MenuContainerDetails(
      route: Routes.assignments,
      iconPath: Assets.assignmentIcon,
      titleKey: assignmentsKey,
    ),
    MenuContainerDetails(
      route: Routes.announcements,
      iconPath: Assets.announcementIcon,
      titleKey: announcementsKey,
    ),
    MenuContainerDetails(
      route: Routes.lessons,
      iconPath: Assets.lessonIcon,
      titleKey: chaptersKey,
    ),
    MenuContainerDetails(
      route: Routes.topics,
      iconPath: Assets.topicsIcon,
      titleKey: topicsKey,
    ),
    MenuContainerDetails(
      route: Routes.academicCalendar,
      iconPath: Assets.holidayIcon,
      titleKey: academicCalendarKey,
    ),
    MenuContainerDetails(
      route: Routes.teacherAttendence,
      iconPath: Assets.attendanceIcon,
      titleKey: attendanceKey,
    ),
    MenuContainerDetails(
      route: Routes.exams,
      iconPath: Assets.examIcon,
      titleKey: examsKey,
    ),
    if (context.read<DashboardCubit>().primaryClass() != null)
      MenuContainerDetails(
        route: Routes.addResultForAllStudents,
        iconPath: Assets.resultIcon,
        titleKey: addResultKey,
      ),
    MenuContainerDetails(
      route: Routes.manageLeaves,
      iconPath: Assets.manageLeavesIcon,
      titleKey: manageLeavesKey,
    ),
    if (context.read<DashboardCubit>().primaryClass() != null)
      MenuContainerDetails(
        route: Routes.manageStudentLeaves,
        iconPath: Assets.manageStudentLeavesIcon,
        titleKey: manageStudentLeavesKey,
      ),
  ];

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<DashboardCubit>().fetchDashboard();
      }
    });
    super.initState();
  }

  Widget _buildTitleText(
    String textKey, {
    bool removePadding = false,
    void Function()? viewAllTap,
  }) {
    return Padding(
      padding: removePadding
          ? EdgeInsets.zero
          : EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              UiUtils.getTranslatedLabel(context, textKey),
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface,
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          if (viewAllTap != null) ...[
            const SizedBox(width: 8),
            GestureDetector(
              onTap: viewAllTap,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    UiUtils.getTranslatedLabel(context, viewAllKey),
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(
                    Icons.arrow_right_alt,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildClassTeacherClasses({
    required List<ClassSectionDetails> classes,
  }) {
    return GridView.count(
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      crossAxisCount: 2,
      childAspectRatio: 1.3,
      children: List.generate(classes.length, (index) {
        return ClassTeacherClassContainer(
          classDetails: classes[index],
          index: index, //for color picking using index
        );
      }),
    );
  }

  Widget _buildSubjectTeacherClasses({
    required List<ClassSectionDetails> classes,
  }) {
    return SizedBox(
      height: 84,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(
          horizontal: MediaQuery.sizeOf(context).width * 0.075,
        ),
        itemBuilder: (context, index) {
          return SubjectTeacherClassContainer(
            classDetails: classes[index],
            index: index, //for color picking using index
          );
        },
        separatorBuilder: (context, index) {
          return const SizedBox(width: 12);
        },
        itemCount: classes.length,
      ),
    );
  }

  Widget _buildExploreAcademics() {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: exploreAcademicsItems.length,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCountAndFixedHeight(
        crossAxisCount: 3,
        height: MediaQuery.sizeOf(context).width / 3 < 150
            ? 120 //fixed height for smaller devices
            : (MediaQuery.sizeOf(context).width / 3) - 16,
        mainAxisSpacing: 8,
        crossAxisSpacing: 8,
      ),
      padding: EdgeInsets.zero,
      itemBuilder: (context, index) {
        return HomeContainerExploreAcademicsItemContainer(
          item: exploreAcademicsItems[index],
        );
      },
    );
  }

  Widget _buildEventList(List<Event> events) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: Column(
        children: List.generate(
          events.length,
          (index) => Animate(
            effects: listItemAppearanceEffects(
              itemIndex: index,
              totalLoadedItems: events.length,
            ),
            child: EventItemContainer(
              event: events[index],
              backgroundColor: Theme.of(context)
                  .colorScheme
                  .surfaceContainerLowest,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildUpcomingExams(List<Exam> exams) {
    return SizedBox(
      height: 130,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(
          horizontal: MediaQuery.sizeOf(context).width * 0.075,
        ),
        itemBuilder: (context, index) {
          return HomeContainerExamItemContainer(exam: exams[index]);
        },
        separatorBuilder: (context, index) => const SizedBox(width: 8),
        itemCount: exams.length,
      ),
    );
  }

  Widget _buildStudentPendingLeaveRequests({
    required List<StudentLeave> leaveRequests,
  }) {
    return SizedBox(
      height: 130,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(
          horizontal: MediaQuery.sizeOf(context).width * 0.075,
        ),
        itemBuilder: (context, index) {
          return BlocProvider(
            create: (context) =>
                UpdateStudentLeaveStatusCubit(StudentLeaveRepository()),
            child: HomeContainerStudentLeaveRequestContainer(
              studentLeave: leaveRequests[index],
            ),
          );
        },
        separatorBuilder: (context, index) => const SizedBox(width: 8),
        itemCount: leaveRequests.length,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bodyContentPadding = EdgeInsets.only(
      bottom: UiUtils.getScrollViewBottomPadding(context),
      top: UiUtils.getScrollViewTopPadding(
        context: context,
        appBarHeightPercentage: UiUtils.appBarMediumHeightPercentage,
      ),
    );
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerLow,
      height: double.maxFinite,
      width: double.maxFinite,
      child: Stack(
        children: [
          Align(
            alignment: AlignmentDirectional.topStart,
            child: BlocBuilder<DashboardCubit, DashboardState>(
              builder: (context, state) {
                if (state is DashboardFetchSuccess) {
                  if ((state.primaryClass?.isEmpty ?? true) &&
                      state.classes.isEmpty) {
                    return Padding(
                      padding: bodyContentPadding,
                      child: Center(
                        child: NoDataContainer(
                          titleKey: noClassAssignedKey,
                          showRetryButton: true,
                          onTapRetry: () {
                            context.read<DashboardCubit>().fetchDashboard();
                          },
                        ),
                      ),
                    );
                  }
                  return RefreshIndicator(
                    displacement: UiUtils.getScrollViewTopPadding(
                      context: context,
                      appBarHeightPercentage:
                          UiUtils.appBarMediumHeightPercentage,
                    ),
                    color: Theme.of(context).colorScheme.primary,
                    onRefresh: () {
                      return context.read<DashboardCubit>().fetchDashboard();
                    },
                    child: SizedBox(
                      height: double.maxFinite,
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: bodyContentPadding,
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const AcademicNextPeriod(),
                            const StaffRecognitionEntry(),

                            ///[Class Teacher]
                            if (state.primaryClass?.isNotEmpty ?? false) ...[
                              _buildTitleText(classTeacherKey),
                              const SizedBox(height: 12),
                              _buildClassTeacherClasses(
                                classes: state.primaryClass ?? [],
                              ),
                              const SizedBox(height: 12),
                            ],

                            ///[Subject Teacher]
                            if (state.classes.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              _buildTitleText(myClassesKey),
                              const SizedBox(height: 12),
                              _buildSubjectTeacherClasses(
                                classes: state.classes,
                              ),
                              const SizedBox(height: 12),
                            ],

                            ///[Student Pending Leave Requests]
                            if (state.pendingLeaveRequests.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              _buildTitleText(
                                studentPendingLeaveRequestsKey,
                                viewAllTap: () {
                                  Navigator.of(context)
                                      .pushNamed(Routes.manageStudentLeaves);
                                },
                              ),
                              const SizedBox(height: 12),
                              _buildStudentPendingLeaveRequests(
                                leaveRequests: state.pendingLeaveRequests,
                              ),
                              const SizedBox(height: 12),
                            ],

                            ///[Explore Academics]
                            const SizedBox(height: 6),
                            Container(
                              color: Theme.of(context)
                                  .colorScheme
                                  .surfaceContainerLowest,
                              padding: EdgeInsets.symmetric(
                                horizontal:
                                    MediaQuery.sizeOf(context).width * 0.075,
                                vertical: 12,
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  _buildTitleText(
                                    exploreAcademicsKey,
                                    removePadding: true,
                                  ),
                                  const SizedBox(height: 12),
                                  _buildExploreAcademics(),
                                ],
                              ),
                            ),
                            const SizedBox(height: 12),

                            ///[Todays Timetable]
                            if (state.todaysTimetable.isNotEmpty) ...[
                              _buildTitleText(todaysTimetableKey),
                              const SizedBox(height: 12),
                              if (context.watch<TimeTableCubit>().state
                                  is TimeTableFetchSuccess)
                                Padding(
                                  padding: EdgeInsets.symmetric(
                                    horizontal:
                                        MediaQuery.sizeOf(context).width *
                                        0.075,
                                  ),
                                  child: HomeContainerTodaysTimetableContainer(
                                    timeTableSlots: state.todaysTimetable,
                                  ),
                                ),
                              const SizedBox(height: 12),
                            ],

                            ///[Upcoming Exams]
                            if (state.upcomingExams.isNotEmpty) ...[
                              ColoredBox(
                                color: Theme.of(context)
                                    .colorScheme
                                    .surfaceContainerLowest,
                                child: Column(
                                  children: [
                                    const SizedBox(height: 16),
                                    _buildTitleText(
                                      upcomingExamsKey,
                                      viewAllTap: () {
                                        Navigator.of(context)
                                            .pushNamed(Routes.exams);
                                      },
                                    ),
                                    const SizedBox(height: 12),
                                    _buildUpcomingExams(state.upcomingExams),
                                    const SizedBox(height: 16),
                                  ],
                                ),
                              ),
                            ],

                            ///[Staff Leaves]
                            if (state.hasStaffLeaves) ...[
                              const SizedBox(height: 18),
                              _buildTitleText(staffLeavesKey),
                              const SizedBox(height: 12),
                              Padding(
                                padding: EdgeInsets.symmetric(
                                  horizontal:
                                      MediaQuery.sizeOf(context).width * 0.075,
                                ),
                                child: HomeContainerStaffLeavesContainer(
                                  today: state.todayStaffLeave,
                                  tomorrow: state.tomorrowStaffLeave,
                                  upcoming: state.upcomingStaffLeave,
                                ),
                              ),
                              const SizedBox(height: 12),
                            ],

                            ///[Upcoming Events]
                            if (state.upcomingEvents.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              _buildTitleText(
                                upcomingEventsKey,
                                viewAllTap: () {
                                  Navigator.of(context)
                                      .pushNamed(Routes.academicCalendar);
                                },
                              ),
                              const SizedBox(height: 12),
                              _buildEventList(state.upcomingEvents),
                            ],

                            ///[Gallery]
                            if (state.gallery != null &&
                                state.gallery!.hasData()) ...[
                              const SizedBox(height: 12),
                              HomeGalleryContainer(
                                albums: state.gallery!.albums ?? [],
                                videos: state.gallery!.videos ?? [],
                              ),
                            ],
                            const SizedBox(height: 24),
                          ],
                        ),
                      ),
                    ),
                  );
                } else if (state is DashboardFetchFailure) {
                  return Padding(
                    padding: bodyContentPadding,
                    child: Center(
                      child: ErrorContainer(
                        errorMessageCode: state.errorMessage,
                        onTapRetry: () {
                          context.read<DashboardCubit>().fetchDashboard();
                        },
                      ),
                    ),
                  );
                } else {
                  return SingleChildScrollView(
                    padding: bodyContentPadding,
                    child: const HomeContainerShimmerContainer(),
                  );
                }
              },
            ),
          ),
          const Align(
            alignment: Alignment.topCenter,
            child: HomeContainerAppBarContainer(),
          ),
        ],
      ),
    );
  }
}
