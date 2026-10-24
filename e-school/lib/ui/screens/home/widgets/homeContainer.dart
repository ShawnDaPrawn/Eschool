import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/data/repositories/settingsRepository.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/home/widgets/homeAssignmentContainer.dart';
import 'package:eschool/ui/screens/home/widgets/homeEventsContainer.dart';
import 'package:eschool/ui/screens/home/widgets/homeTimetableContainer.dart';
import 'package:eschool/ui/screens/home/widgets/homeUpcomingExamsContainer.dart';
import 'package:eschool/ui/widgets/borderedProfilePictureContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/latestNoticesContainer.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/notificationIconWidget.dart';
import 'package:eschool/app/sessionWatcher.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/albumGridItemLoadingContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/announcementShimmerLoadingContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/subjectsShimmerLoadingContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/timetableShimmerLoader.dart';
import 'package:eschool/ui/widgets/slidersContainer.dart';
import 'package:eschool/ui/widgets/studentSubjectsContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class HomeContainer extends StatefulWidget {
  const HomeContainer({super.key});

  @override
  State<HomeContainer> createState() => _HomeContainerState();
}

class _HomeContainerState extends State<HomeContainer> {
  @override
  void initState() {
    super.initState();

    Future.delayed(Duration.zero, () async {
      context.read<StudentDashboardCubit>().fetchDashboard();
      final count = await SettingsRepository().getNotificationCount();
      //if there were any notifications while app was closed, the hive box will have it's accurate value
      notificationCountValueNotifier.value = count;
    });
  }

  Widget _buildTopProfileContainer(BuildContext context) {
    return ScreenTopBackgroundContainer(
      padding: EdgeInsets.zero,
      heightPercentage: UiUtils.homeAndChatMessagePageAppbarHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              //Bordered circles
              PositionedDirectional(
                top: MediaQuery.sizeOf(context).width * (-0.15),
                start: MediaQuery.sizeOf(context).width * (-0.225),
                child: Container(
                  padding: const EdgeInsetsDirectional.only(
                    end: 20,
                    bottom: 20,
                  ),
                  decoration: BoxDecoration(
                    border: Border.all(
                      color: Theme.of(context).colorScheme.onInverseSurface
                          .withValues(alpha: 0.1),
                    ),
                    shape: BoxShape.circle,
                  ),
                  width: MediaQuery.sizeOf(context).width * 0.5,
                  height: MediaQuery.sizeOf(context).width * 0.5,
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: Theme.of(context).colorScheme.onInverseSurface
                            .withValues(alpha: 0.1),
                      ),
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),

              //bottom fill circle
              PositionedDirectional(
                bottom: MediaQuery.sizeOf(context).width * (-0.1),
                end: MediaQuery.sizeOf(context).width * (-0.15),
                child: Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.onInverseSurface
                        .withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  width: MediaQuery.sizeOf(context).width * 0.35,
                  height: MediaQuery.sizeOf(context).width * 0.35,
                ),
              ),

              Align(
                alignment: Alignment.bottomCenter,
                child: Container(
                  margin: EdgeInsetsDirectional.only(
                    start: boxConstraints.maxWidth * 0.05,
                    bottom: boxConstraints.maxHeight * 0.1,
                    end: boxConstraints.maxWidth * 0.05,
                  ),
                  child: Row(
                    children: [
                      BorderedProfilePictureContainer(
                        boxConstraints: boxConstraints,
                        onTap: () {
                          Navigator.of(context).pushNamed(
                            Routes.studentProfile,
                            arguments: context
                                .read<AuthCubit>()
                                .getStudentDetails(),
                          );
                        },
                        imageUrl: context
                            .read<AuthCubit>()
                            .getStudentDetails()
                            .image,
                      ),
                      SizedBox(width: boxConstraints.maxWidth * 0.05),
                      Expanded(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              context
                                  .read<AuthCubit>()
                                  .getStudentDetails()
                                  .getFullName(),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w500,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onInverseSurface,
                              ),
                            ),
                            Row(
                              children: [
                                Flexible(
                                  flex: 2,
                                  child: Text(
                                    '${UiUtils.getTranslatedLabel(context, classKey)}: ${context.read<AuthCubit>().getStudentDetails().classSectionNameWithTerm(context: context)}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w400,
                                      color: Theme.of(context)
                                          .colorScheme
                                          .onInverseSurface,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Container(
                                  width: 1.5,
                                  height: 12,
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onInverseSurface,
                                ),
                                const SizedBox(width: 10),
                                Flexible(
                                  child: Text(
                                    '${UiUtils.getTranslatedLabel(context, rollNoKey)}: ${context.read<AuthCubit>().getStudentDetails().rollNumber}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w400,
                                      color: Theme.of(context)
                                          .colorScheme
                                          .onInverseSurface,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      //only for a learner at more than one school
                      const SchoolSwitcherButton(
                        onSwitched: returnHomeAfterSchoolSwitch,
                      ),
                      const NotificationIconWidget(),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _assignmentListContainer({required List<Assignment> assignments}) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal:
            MediaQuery.sizeOf(context).width *
            UiUtils.screenContentHorizontalPaddingInPercentage,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text(
              UiUtils.getTranslatedLabel(context, assignmentsKey),
              style: TextStyle(
                color: UiUtils.getColorScheme(context).onSurface,
                fontWeight: FontWeight.w600,
                fontSize: 16,
              ),
              textAlign: TextAlign.start,
            ),
          ),
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: List.generate(assignments.length, (index) {
              return HomeAssignmentContainer(
                assignment: assignments[index],
                parentContext: context,
              );
            }),
          ),
        ],
      ),
    );
  }

  Widget _timetableListContainer({required List<TimeTableSlot> timetables}) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal:
            MediaQuery.sizeOf(context).width *
            UiUtils.screenContentHorizontalPaddingInPercentage,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text(
              UiUtils.getTranslatedLabel(context, todaysTimetableKey),
              style: TextStyle(
                color: UiUtils.getColorScheme(context).onSurface,
                fontWeight: FontWeight.w600,
                fontSize: 16,
              ),
              textAlign: TextAlign.start,
            ),
          ),
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          HomeTimetableContainer(timeTableSlots: timetables),
        ],
      ),
    );
  }

  Widget _upcomingExamsContainer({required List<Exam> exam}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Align(
          alignment: AlignmentDirectional.centerStart,
          child: Padding(
            padding: EdgeInsets.symmetric(
              horizontal:
                  MediaQuery.sizeOf(context).width *
                  UiUtils.screenContentHorizontalPaddingInPercentage,
            ),
            child: Text(
              UiUtils.getTranslatedLabel(context, upcomingExamsKey),
              style: TextStyle(
                color: UiUtils.getColorScheme(context).onSurface,
                fontWeight: FontWeight.w600,
                fontSize: 16,
              ),
              textAlign: TextAlign.start,
            ),
          ),
        ),
        SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
        SizedBox(
          height: 130,
          child: ListView.builder(
            padding: EdgeInsetsDirectional.only(
              start:
                  MediaQuery.sizeOf(context).width *
                  UiUtils.screenContentHorizontalPaddingInPercentage,
            ),
            scrollDirection: Axis.horizontal,
            itemCount: exam.length,
            itemBuilder: (context, index) {
              return HomeUpcomingExamsContainer(exam: exam[index]);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildAdvertisementSliders() {
    final sliders = context.read<StudentDashboardCubit>().getSliders();
    return SlidersContainer(sliders: sliders);
  }

  Widget _buildStudentHomeItems() {
    return BlocConsumer<StudentDashboardCubit, StudentDashboardState>(
      listener: (context, state) {
        if (state is StudentDashboardFetchSuccess) {
          if (state.electiveSubjects.isEmpty &&
              state.doesClassHaveElectiveSubjects) {
            Navigator.of(context).pushNamed(Routes.selectSubjects);
          }
        }
      },
      builder: (context, state) {
        if (state is StudentDashboardFetchSuccess) {
          return RefreshIndicator(
            displacement: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage:
                  UiUtils.homeAndChatMessagePageAppbarHeightPercentage,
            ),
            color: Theme.of(context).colorScheme.primary,
            onRefresh: () async {
              context.read<StudentDashboardCubit>().fetchDashboard();
            },
            child: SizedBox(
              height: double.maxFinite,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.homeAndChatMessagePageAppbarHeightPercentage,
                  ),
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                ),
                child: Column(
                  children: [
                    _buildAdvertisementSliders(),
                    SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
                    if (context
                        .read<StudentDashboardCubit>()
                        .getSubjects()
                        .isNotEmpty) ...[
                      StudentSubjectsContainer(
                        subjects: context
                            .read<StudentDashboardCubit>()
                            .getSubjects(),
                        subjectsTitleKey: mySubjectsKey,
                      ),
                    ] else ...[
                      const NoDataContainer(titleKey: noSubjectsKey),
                    ],
                    if (context
                        .read<StudentDashboardCubit>()
                        .getAssignedAssignments()
                        .isNotEmpty) ...[
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      _assignmentListContainer(
                        assignments: context
                            .read<StudentDashboardCubit>()
                            .getAssignedAssignments(),
                      ),
                    ],
                    if (state.todaysTimetable.isNotEmpty) ...[
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      _timetableListContainer(
                        timetables: state.todaysTimetable,
                      ),
                    ],
                    if (state.newAnnouncements.isNotEmpty) ...[
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      const LatestNoticesContainer(),
                    ],
                    if (state.events.isNotEmpty) ...[
                      HomeEventsContainer(events: state.events),
                    ],
                    if (state.upcomingExams.isNotEmpty) ...[
                      _upcomingExamsContainer(exam: state.upcomingExams),
                    ],
                    if (state.gallery != null && state.gallery!.hasData()) ...[
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      HomeGalleryContainer(
                        albums: state.gallery!.albums ?? [],
                        videos: state.gallery!.videos ?? [],
                      ),
                    ],
                    SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
                  ],
                ),
              ),
            ),
          );
        }

        if (state is StudentDashboardFetchFailure) {
          return Center(
            child: ErrorContainer(
              onTapRetry: () {
                context.read<StudentDashboardCubit>().fetchDashboard();
              },
              errorMessageCode: state.errorMessage,
            ),
          );
        }

        return ListView(
          padding: EdgeInsets.only(
            top: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage:
                  UiUtils.homeAndChatMessagePageAppbarHeightPercentage,
            ),
          ),
          children: [
            ShimmerLoadingContainer(
              child: CustomShimmerContainer(
                margin: EdgeInsets.symmetric(
                  horizontal: MediaQuery.sizeOf(context).width * 0.075,
                ),
                width: MediaQuery.sizeOf(context).width,
                borderRadius: 25,
                height:
                    MediaQuery.sizeOf(context).height *
                    UiUtils.homeAndChatMessagePageAppbarHeightPercentage,
              ),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            const SubjectsShimmerLoadingContainer(),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            Column(
              children: List.generate(3, (index) => index)
                  .map((notice) => const TimetableShimmerLoadingContainer())
                  .toList(),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            Column(
              children: List.generate(3, (index) => index)
                  .map((notice) => const AnnouncementShimmerLoadingContainer())
                  .toList(),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: EdgeInsets.symmetric(
                horizontal: MediaQuery.sizeOf(context).width * 0.075,
              ),
              child: Row(
                spacing: 16,
                children: List.generate(
                  3,
                  (index) => index,
                ).map((e) => const AlbumGridItemLoadingContainer()).toList(),
              ),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Align(alignment: Alignment.topCenter, child: _buildStudentHomeItems()),
        Align(
          alignment: Alignment.topCenter,
          child: _buildTopProfileContainer(context),
        ),
      ],
    );
  }
}
