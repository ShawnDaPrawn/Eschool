import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/noticeBoardCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/announcementDetailsContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/announcementShimmerLoadingContainer.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class LatestNoticesContainer extends StatelessWidget {
  const LatestNoticesContainer({super.key, this.animate = true});
  final bool animate;

  Widget _announcementsSuccessItems(List<Announcement> announcements) {
    return Column(
      children: List.generate(
        announcements.length,
        (index) => Animate(
          effects: animate
              ? listItemAppearanceEffects(
                  itemIndex: index,
                  totalLoadedItems: announcements.length,
                )
              : null,
          child: AnnouncementDetailsContainer(
            announcement: announcements[index],
          ),
        ),
      ),
    );
  }

  Widget _announcementsLoading() {
    return Column(
      children: List.generate(
        3,
        (index) => index,
      ).map((notice) => const AnnouncementShimmerLoadingContainer()).toList(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<NoticeBoardCubit, NoticeBoardState>(
      buildWhen: (previous, current) => current is NoticeBoardFetchSuccess,
      builder: (context, state) {
        if (state is NoticeBoardFetchSuccess &&
            state.announcements.isEmpty &&
            context.read<AuthCubit>().isParent()) {
          return const SizedBox.shrink();
        }
        return Container(
          width: MediaQuery.sizeOf(context).width,
          margin: EdgeInsets.symmetric(
            horizontal:
                MediaQuery.sizeOf(context).width *
                UiUtils.screenContentHorizontalPaddingInPercentage,
          ),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    UiUtils.getTranslatedLabel(context, latestNoticesKey),
                    style: TextStyle(
                      color: UiUtils.getColorScheme(context).onSurface,
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                    textAlign: TextAlign.start,
                  ),
                  InkWell(
                    onTap: () {
                      Navigator.of(context).pushNamed(Routes.noticeBoard);
                    },
                    child: Text(
                      UiUtils.getTranslatedLabel(context, viewAllKey),
                      style: TextStyle(
                        color: UiUtils.getColorScheme(context).onSurfaceVariant,
                        fontSize: 13,
                      ),
                      textAlign: TextAlign.start,
                    ),
                  ),
                ],
              ),
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              if (context.read<AuthCubit>().isParent()) ...[
                BlocBuilder<NoticeBoardCubit, NoticeBoardState>(
                  builder: (context, state) {
                    if (state is NoticeBoardFetchSuccess) {
                      final announcements =
                          state.announcements.length >
                              numberOfLatestNoticesInHomeScreen
                          ? state.announcements
                                .sublist(0, numberOfLatestNoticesInHomeScreen)
                                .toList()
                          : state.announcements;
                      return _announcementsSuccessItems(announcements);
                    }

                    if (state is NoticeBoardFetchInProgress ||
                        state is NoticeBoardInitial) {
                      return _announcementsLoading();
                    }

                    return const SizedBox();
                  },
                ),
              ] else ...[
                //student's notices will be in dashboard always and it'll be success only
                BlocBuilder<StudentDashboardCubit, StudentDashboardState>(
                  builder: (context, state) {
                    if (state is StudentDashboardFetchSuccess) {
                      final announcements =
                          state.newAnnouncements.length >
                              numberOfLatestNoticesInHomeScreen
                          ? state.newAnnouncements
                                .sublist(0, numberOfLatestNoticesInHomeScreen)
                                .toList()
                          : state.newAnnouncements;
                      return _announcementsSuccessItems(announcements);
                    }

                    if (state is StudentDashboardFetchInProgress ||
                        state is StudentDashboardInitial) {
                      return _announcementsLoading();
                    }

                    return const SizedBox();
                  },
                ),
              ],
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            ],
          ),
        );
      },
    );
  }
}
