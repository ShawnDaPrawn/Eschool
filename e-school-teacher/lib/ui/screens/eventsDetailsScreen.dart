// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool_teacher/cubits/eventDetailsCubit.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/data/models/eventSchedule.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/ui/screens/academicCalendar/widgets/listItemForEvents.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:readmore/readmore.dart';

class EventDetailsScreen extends StatefulWidget {
  final Event event;
  const EventDetailsScreen({required this.event, super.key});

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => BlocProvider<EventDetailsCubit>(
        create: (context) => EventDetailsCubit(SystemRepository()),
        child: EventDetailsScreen(event: arguments['event']),
      ),
    );
  }

  @override
  State<EventDetailsScreen> createState() => _EventDetailsScreenState();
}

class _EventDetailsScreenState extends State<EventDetailsScreen> {
  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<EventDetailsCubit>().fetchEventDetails(
          eventId: widget.event.id.toString(),
        );
      }
    });
    super.initState();
  }

  Widget _buildAppBar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const CustomBackButton(),
          Align(
            alignment: Alignment.topCenter,
            child: Padding(
              padding: EdgeInsetsDirectional.only(
                start: 30 + UiUtils.screenContentHorizontalPadding,
                end: UiUtils.screenContentHorizontalPadding,
              ), //padding to not hit the back button
              child: Text(
                widget.event.title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).scaffoldBackgroundColor,
                  fontSize: UiUtils.screenTitleFontSize,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget buildEventDayShimmer() {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 8,
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: const ShimmerLoadingContainer(
        child: CustomShimmerContainer(height: 80, borderRadius: 10),
      ),
    );
  }

  Container _singleDayItem({required EventSchedule daySchedule}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.onPrimary,
        borderRadius: BorderRadius.circular(10),
      ),
      width: double.maxFinite,
      clipBehavior: Clip.antiAlias,
      child: Row(
        children: [
          if (daySchedule.endTime != null && daySchedule.startTime != null)
            Container(
              constraints: const BoxConstraints(maxWidth: 100),
              padding: const EdgeInsets.all(10),
              child: Text(
                '${UiUtils.formatTime(daySchedule.startTime!)}\n${UiUtils.getTranslatedLabel(context, toKey)}\n${UiUtils.formatTime(daySchedule.endTime!)}',
                style: TextStyle(
                  height: 1.3,
                  color: Theme.of(context).scaffoldBackgroundColor,
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
                textAlign: TextAlign.center,
                maxLines: 5,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          Expanded(
            child: Container(
              constraints: const BoxConstraints(minHeight: 70),
              color: Theme.of(context).colorScheme.surface,
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    daySchedule.title,
                    style: TextStyle(
                      height: 1,
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w500,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.start,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 5),
                  ReadMoreText(
                    daySchedule.description,
                    trimLines: 3,
                    colorClickableText: primaryColor,
                    trimMode: TrimMode.Line,
                    trimCollapsedText: UiUtils.getTranslatedLabel(
                      context,
                      showMoreKey,
                    ),
                    trimExpandedText:
                        ' ${UiUtils.getTranslatedLabel(context, showLessKey)}',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w400,
                      fontSize: 12,
                    ),
                    textAlign: TextAlign.start,
                  ),
                  const SizedBox(height: 10),
                  Text.rich(
                    TextSpan(
                      children: [
                        WidgetSpan(
                          alignment: PlaceholderAlignment.middle,
                          child: Padding(
                            padding: const EdgeInsetsDirectional.only(end: 2),
                            child: Icon(
                              Icons.calendar_month_outlined,
                              color: Theme.of(context).colorScheme.secondary,
                              size: 12,
                            ),
                          ),
                        ),
                        TextSpan(text: UiUtils.formatDate(daySchedule.date)),
                      ],
                      style: TextStyle(
                        height: 1,
                        color: Theme.of(context).colorScheme.secondary,
                        fontWeight: FontWeight.w400,
                        fontSize: 10,
                      ),
                    ),
                    textAlign: TextAlign.start,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShimmerLoader() {
    return ShimmerLoadingContainer(
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return SizedBox(
            height: double.maxFinite,
            child: ListView.builder(
              padding: EdgeInsets.zero,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: UiUtils.defaultShimmerLoadingContentCount,
              itemBuilder: (context, index) {
                return _buildOneEventShimmerLoader();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildOneEventShimmerLoader() {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 10,
        horizontal: MediaQuery.sizeOf(context).width,
      ),
      child: const ShimmerLoadingContainer(
        child: CustomShimmerContainer(height: 70, borderRadius: 10),
      ),
    );
  }

  SizedBox _buildEventDetails() {
    return SizedBox(
      height: double.maxFinite,
      width: double.maxFinite,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.only(
          top: UiUtils.getScrollViewTopPadding(
            context: context,
            appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
          ),
          bottom: UiUtils.getScrollViewBottomPadding(context),
          right: MediaQuery.sizeOf(context).width * 0.075,
          left: MediaQuery.sizeOf(context).width * 0.075,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            EventItemContainer(event: widget.event, isDetailsScreen: true),
            BlocBuilder<EventDetailsCubit, EventDetailsState>(
              builder: (context, state) {
                if (state is EventDetailsFetchSuccess) {
                  return state.eventDetails.isEmpty
                      ? const NoDataContainer(titleKey: noEventsKey)
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          //list of days
                          children: List.generate(
                            state.sortedDates.length,
                            (index) => Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 10),
                                Text(
                                  '${UiUtils.getTranslatedLabel(context, dayKey)} ${index + 1}',
                                  style: TextStyle(
                                    height: 1,
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.primary,
                                    fontWeight: FontWeight.w500,
                                    fontSize: 16,
                                  ),
                                  textAlign: TextAlign.start,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 15),
                                //list of schedules in a day
                                for (
                                  int itemIndex = 0;
                                  itemIndex <
                                      state.eventDetails
                                          .where(
                                            (element) =>
                                                element.date ==
                                                state.sortedDates[index],
                                          )
                                          .length;
                                  itemIndex++
                                )
                                  Animate(
                                    effects: isApplicationItemAnimationOn
                                        ? listItemAppearanceEffects(
                                            itemIndex: index,
                                          )
                                        : [],
                                    child: _singleDayItem(
                                      daySchedule: state.eventDetails
                                          .where(
                                            (element) =>
                                                element.date ==
                                                state.sortedDates[index],
                                          )
                                          .toList()[itemIndex],
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        );
                }
                if (state is EventDetailsFetchFailure) {
                  return Center(
                    child: ErrorContainer(
                      errorMessageCode: state.errorMessage,
                      onTapRetry: () {
                        context.read<EventDetailsCubit>().fetchEventDetails(
                          eventId: widget.event.id.toString(),
                        );
                      },
                    ),
                  );
                }
                return Padding(
                  padding: EdgeInsets.only(
                    top: UiUtils.getScrollViewTopPadding(
                      context: context,
                      appBarHeightPercentage:
                          UiUtils.appBarSmallerHeightPercentage,
                    ),
                  ),
                  child: _buildShimmerLoader(),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          _buildEventDetails(),
          Align(alignment: Alignment.topCenter, child: _buildAppBar(context)),
        ],
      ),
    );
  }
}
