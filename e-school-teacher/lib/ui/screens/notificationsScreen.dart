// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool_teacher/cubits/notificationCubit.dart';
import 'package:eschool_teacher/data/models/customNotification.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/customRefreshIndicator.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/loadMoreErrorWidget.dart';
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

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => BlocProvider(
        create: (context) => NotificationsCubit(TeacherRepository()),
        child: const NotificationScreen(),
      ),
    );
  }

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_notificationsScrollListener);

  void _notificationsScrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<NotificationsCubit>().hasMore()) {
        context.read<NotificationsCubit>().fetchMoreNotifications();
      }
    }
  }

  @override
  void initState() {
    Future.delayed(Duration.zero, () async {
      fetchnotifications();
      notificationCountValueNotifier.value = 0;
      await SettingsRepository().setNotificationCount(0);
    });
    super.initState();
  }

  @override
  void dispose() {
    _scrollController.removeListener(_notificationsScrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void fetchnotifications() {
    context.read<NotificationsCubit>().fetchNotifications(page: 1);
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
            child: Text(
              UiUtils.getTranslatedLabel(context, notificationsKey),
              style: TextStyle(
                color: Theme.of(context).scaffoldBackgroundColor,
                fontSize: UiUtils.screenTitleFontSize,
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
                return _buildOnenotificationShimmerLoader();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildOnenotificationShimmerLoader() {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 8,
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: const ShimmerLoadingContainer(
        child: CustomShimmerContainer(height: 100, borderRadius: 10),
      ),
    );
  }

  Widget _buildSinglenotificationItem({
    required CustomNotification notification,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      width: MediaQuery.sizeOf(context).width * 0.85,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Padding(
            padding: const EdgeInsets.all(15),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        notification.title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          height: 1.2,
                          color: Theme.of(context).colorScheme.secondary,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(height: 2),
                      ReadMoreText(
                        notification.message,
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
                      const SizedBox(height: 5),
                      Row(
                        children: [
                          Icon(
                            Icons.calendar_month_outlined,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.75),
                            size: 14,
                          ),
                          const SizedBox(width: 5),
                          Expanded(
                            child: Text(
                              UiUtils.getTimeAgo(
                                context,
                                date: notification.date,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurface.withValues(alpha: 0.75),
                                fontWeight: FontWeight.w400,
                                fontSize: 11,
                              ),
                              textAlign: TextAlign.start,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 5),
                if (notification.image == null)
                  const SizedBox.shrink()
                else
                  CachedNetworkImage(
                    errorWidget: (context, image, _) => const SizedBox.shrink(),
                    imageBuilder: (context, imageProvider) {
                      return Container(
                        alignment: Alignment.topCenter,
                        child: Container(
                          width: 70,
                          height: 70,
                          decoration: BoxDecoration(
                            image: DecorationImage(
                              image: imageProvider,
                              fit: BoxFit.cover,
                            ),
                            borderRadius: BorderRadius.circular(5),
                          ),
                        ),
                      );
                    },
                    imageUrl: storageUrl + notification.image!,
                    placeholder: (context, url) => const SizedBox.shrink(),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          BlocBuilder<NotificationsCubit, NotificationState>(
            builder: (context, state) {
              if (state is NotificationFetchSuccess) {
                return state.notifications.isEmpty
                    ? Padding(
                        padding: EdgeInsets.only(
                          top: UiUtils.getScrollViewTopPadding(
                            context: context,
                            appBarHeightPercentage:
                                UiUtils.appBarSmallerHeightPercentage,
                          ),
                        ),
                        child: const NoDataContainer(
                          titleKey: noNotificationsKey,
                        ),
                      )
                    : CustomRefreshIndicator(
                        displacement: UiUtils.getScrollViewTopPadding(
                          context: context,
                          appBarHeightPercentage:
                              UiUtils.appBarSmallerHeightPercentage,
                        ),
                        onRefreshCallback: () {
                          if (!context.read<NotificationsCubit>().isLoading()) {
                            fetchnotifications();
                          }
                        },
                        child: SizedBox(
                          height: double.maxFinite,
                          width: double.maxFinite,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: EdgeInsets.only(
                              top: UiUtils.getScrollViewTopPadding(
                                context: context,
                                appBarHeightPercentage:
                                    UiUtils.appBarSmallerHeightPercentage,
                              ),
                              bottom: UiUtils.getScrollViewBottomPadding(
                                context,
                              ),
                            ),
                            child: Column(
                              children:
                                  List.generate(state.notifications.length, (
                                    index,
                                  ) {
                                    {
                                      return Animate(
                                        effects: listItemAppearanceEffects(
                                          itemIndex: index,
                                        ),
                                        child: _buildSinglenotificationItem(
                                          notification:
                                              state.notifications[index],
                                        ),
                                      );
                                    }
                                  })..addAll([
                                    if (state.moreNotificationsFetchInProgress)
                                      _buildOnenotificationShimmerLoader(),
                                    if (state.moreNotificationsFetchError &&
                                        !state.moreNotificationsFetchInProgress)
                                      LoadMoreErrorWidget(
                                        onTapRetry: () {
                                          context
                                              .read<NotificationsCubit>()
                                              .fetchMoreNotifications();
                                        },
                                      ),
                                  ]),
                            ),
                          ),
                        ),
                      );
              }
              if (state is NotificationFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      context.read<NotificationsCubit>().fetchNotifications(
                        page: 1,
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
          Align(alignment: Alignment.topCenter, child: _buildAppBar(context)),
        ],
      ),
    );
  }
}
