import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/chat/chatUsersCubit.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class HomeContainerAppBarContainer extends StatelessWidget {
  const HomeContainerAppBarContainer({super.key});

  Widget _buildChatIcon(BuildContext context) {
    return BlocBuilder<ParentChatUserCubit, ChatUsersState>(
      builder: (context, stateParentChats) {
        return BlocBuilder<StudentChatUsersCubit, ChatUsersState>(
          builder: (context, stateChildChats) {
            return GestureDetector(
              onTap: () {
                Navigator.of(context).pushNamed(Routes.chatUserPage);
              },
              child: SizedBox(
                height: 40,
                width: 40,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    SvgPicture.asset(
                      Assets.chatIcon,
                      width: 25,
                      height: 25,
                      colorFilter: const ColorFilter.mode(
                        Colors.white,
                        BlendMode.srcIn,
                      ),
                    ),
                    //notification count showing on top of the icon
                    if (stateParentChats is ChatUsersFetchSuccess &&
                        stateChildChats is ChatUsersFetchSuccess &&
                        stateParentChats.totalUnreadUsers +
                                stateChildChats.totalUnreadUsers !=
                            0)
                      Align(
                        alignment: AlignmentDirectional.topEnd,
                        child: Container(
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            color: redColor,
                          ),
                          padding: const EdgeInsets.all(4),
                          child: Text(
                            stateParentChats.totalUnreadUsers +
                                        stateChildChats.totalUnreadUsers >
                                    9
                                ? '9+'
                                : (stateParentChats.totalUnreadUsers +
                                          stateChildChats.totalUnreadUsers)
                                      .toString(),
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return ScreenTopBackgroundContainer(
      padding: const EdgeInsets.all(0),
      heightPercentage: UiUtils.appBarMediumHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              //Bordered circles
              PositionedDirectional(
                top: MediaQuery.sizeOf(context).width * (-0.15),
                start: MediaQuery.sizeOf(context).width * (-0.225),
                child: Container(
                  padding: const EdgeInsets.only(right: 20, bottom: 20),
                  decoration: BoxDecoration(
                    border: Border.all(
                      color: Theme.of(
                        context,
                      ).scaffoldBackgroundColor.withValues(alpha: 0.1),
                    ),
                    shape: BoxShape.circle,
                  ),
                  width: MediaQuery.sizeOf(context).width * 0.6,
                  height: MediaQuery.sizeOf(context).width * 0.6,
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: Theme.of(
                          context,
                        ).scaffoldBackgroundColor.withValues(alpha: 0.1),
                      ),
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),

              //bottom fill circle
              PositionedDirectional(
                bottom: MediaQuery.sizeOf(context).width * (-0.15),
                end: MediaQuery.sizeOf(context).width * (-0.15),
                child: Container(
                  decoration: BoxDecoration(
                    color: Theme.of(
                      context,
                    ).scaffoldBackgroundColor.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  width: MediaQuery.sizeOf(context).width * 0.4,
                  height: MediaQuery.sizeOf(context).width * 0.4,
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
                      Container(
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(
                            width: 2,
                            color: Theme.of(context).scaffoldBackgroundColor,
                          ),
                        ),
                        width: boxConstraints.maxWidth * 0.175,
                        height: boxConstraints.maxWidth * 0.175,
                        child: CustomUserProfileImageWidget(
                          profileUrl: context
                              .read<AuthCubit>()
                              .getTeacherDetails()
                              .image,
                        ),
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
                                  .getTeacherDetails()
                                  .getFullName(),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w500,
                                color: Theme.of(
                                  context,
                                ).scaffoldBackgroundColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                      _buildChatIcon(context),
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
}
