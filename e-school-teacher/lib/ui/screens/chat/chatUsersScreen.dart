import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/chat/chatUsersCubit.dart';
import 'package:eschool_teacher/ui/screens/chat/widget/chatUserItem.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/appBarTitleContainer.dart';
import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/customTabBarContainer.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/loadMoreErrorWidget.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChatUsersScreen extends StatefulWidget {
  const ChatUsersScreen({super.key});

  static CupertinoPageRoute route(RouteSettings routeSettings) {
    return CupertinoPageRoute(builder: (_) => const ChatUsersScreen());
  }

  @override
  State<ChatUsersScreen> createState() => _ChatUsersScreenState();
}

class _ChatUsersScreenState extends State<ChatUsersScreen> {
  final PageController _pageController = PageController();
  final ValueNotifier<String> _selectedTabTitle = ValueNotifier(studentsKey);

  late final ScrollController _studentScrollController = ScrollController()
    ..addListener(_studentScrollListener);

  late final ScrollController _parentScrollController = ScrollController()
    ..addListener(_parentScrollListener);

  void _parentScrollListener() {
    if (_parentScrollController.hasClients) {
      if (_parentScrollController.offset >=
          _parentScrollController.position.maxScrollExtent) {
        if (context.read<ParentChatUserCubit>().hasMore()) {
          context.read<ParentChatUserCubit>().fetchMoreChatUsers();
        }
      }
    }
  }

  void _studentScrollListener() {
    if (_studentScrollController.hasClients) {
      if (_studentScrollController.offset >=
          _studentScrollController.position.maxScrollExtent) {
        if (context.read<StudentChatUsersCubit>().hasMore()) {
          context.read<StudentChatUsersCubit>().fetchMoreChatUsers();
        }
      }
    }
  }

  Widget _buildUnreadCounter({required int count}) {
    return count == 0
        ? const SizedBox.shrink()
        : Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              color: greenColor.withValues(alpha: .8),
            ),
            margin: const EdgeInsetsDirectional.only(start: 5),
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              (count > 999) ? '999+' : count.toString(),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
          );
  }

  @override
  void dispose() {
    _studentScrollController.removeListener(_studentScrollListener);
    _parentScrollController.removeListener(_parentScrollListener);
    _parentScrollController.dispose();
    _studentScrollController.dispose();
    _pageController.dispose();
    super.dispose();
  }

  void fetchChatUsers() {
    if (_selectedTabTitle.value == parentsKey) {
      context.read<ParentChatUserCubit>().fetchChatUsers();
    } else {
      context.read<StudentChatUsersCubit>().fetchChatUsers();
    }
  }

  Widget _buildAppBar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarBiggerHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return ValueListenableBuilder<String>(
            valueListenable: _selectedTabTitle,
            builder: (context, selectedTitle, _) => Stack(
              clipBehavior: Clip.none,
              children: [
                const CustomBackButton(),
                Align(
                  alignment: AlignmentDirectional.topEnd,
                  child: Padding(
                    padding: const EdgeInsetsDirectional.only(end: 20, top: 5),
                    child: InkWell(
                      onTap: () {
                        Navigator.pushNamed(context, Routes.chatUserSearch);
                      },
                      child: Icon(
                        Icons.search,
                        size: 24,
                        color: Theme.of(context).scaffoldBackgroundColor,
                      ),
                    ),
                  ),
                ),
                AppBarTitleContainer(
                  boxConstraints: boxConstraints,
                  title: UiUtils.getTranslatedLabel(context, chatKey),
                ),
                AnimatedAlign(
                  curve: UiUtils.tabBackgroundContainerAnimationCurve,
                  duration: UiUtils.tabBackgroundContainerAnimationDuration,
                  alignment: selectedTitle == studentsKey
                      ? AlignmentDirectional.centerStart
                      : AlignmentDirectional.centerEnd,
                  child: TabBackgroundContainer(boxConstraints: boxConstraints),
                ),
                CustomTabBarContainer(
                  boxConstraints: boxConstraints,
                  alignment: AlignmentDirectional.centerStart,
                  isSelected: selectedTitle == studentsKey,
                  onTap: () {
                    if (_selectedTabTitle.value != studentsKey) {
                      _pageController.jumpToPage(0);
                      _selectedTabTitle.value = studentsKey;
                    }
                  },
                  customPostFix:
                      BlocBuilder<StudentChatUsersCubit, ChatUsersState>(
                        builder: (context, state) {
                          return (state is ChatUsersFetchSuccess)
                              ? _buildUnreadCounter(
                                  count: state.totalUnreadUsers,
                                )
                              : const SizedBox.shrink();
                        },
                      ),
                  titleKey: studentsKey,
                ),
                CustomTabBarContainer(
                  boxConstraints: boxConstraints,
                  alignment: AlignmentDirectional.centerEnd,
                  isSelected: selectedTitle == parentsKey,
                  onTap: () {
                    if (_selectedTabTitle.value != parentsKey) {
                      _pageController.jumpToPage(1);
                      _selectedTabTitle.value = parentsKey;
                    }
                  },
                  customPostFix:
                      BlocBuilder<ParentChatUserCubit, ChatUsersState>(
                        builder: (context, state) {
                          return (state is ChatUsersFetchSuccess)
                              ? _buildUnreadCounter(
                                  count: state.totalUnreadUsers,
                                )
                              : const SizedBox.shrink();
                        },
                      ),
                  titleKey: parentsKey,
                ),
              ],
            ),
          );
        },
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
                return _buildOneChatUserShimmerLoader();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildOneChatUserShimmerLoader() {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 8,
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: const ShimmerLoadingContainer(
        child: CustomShimmerContainer(height: 80, borderRadius: 12),
      ),
    );
  }

  Widget _stateItemsBuilder({
    required BuildContext context,
    required ChatUsersState state,
    required ChatUsersCubit chatUsersCubit,
    required ScrollController scrollController,
  }) {
    if (state is ChatUsersFetchSuccess) {
      return state.chatUsers.isEmpty
          ? const NoDataContainer(titleKey: noChatUsersKey)
          : Padding(
              padding: EdgeInsetsDirectional.only(
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  keepExtraSpace: false,
                  appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
                ),
              ),
              child: SizedBox(
                height: double.maxFinite,
                width: double.maxFinite,
                child: SingleChildScrollView(
                  controller: scrollController,
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: Column(
                    children: [
                      ...List.generate(state.chatUsers.length, (index) {
                        final currentChatUser = state.chatUsers[index];
                        return Animate(
                          effects: customItemFadeAppearanceEffects(),
                          child: ChatUserItemWidget(chatUser: currentChatUser),
                        );
                      }),
                      if (state.moreChatUserFetchProgress)
                        _buildOneChatUserShimmerLoader(),
                      if (state.moreChatUserFetchError &&
                          !state.moreChatUserFetchProgress)
                        LoadMoreErrorWidget(
                          onTapRetry: () {
                            chatUsersCubit.fetchMoreChatUsers();
                          },
                        ),
                      SizedBox(
                        height: UiUtils.getScrollViewBottomPadding(context),
                      ),
                    ],
                  ),
                ),
              ),
            );
    }
    if (state is ChatUsersFetchFailure) {
      return Center(
        child: ErrorContainer(
          errorMessageCode: state.errorMessage,
          onTapRetry: () {
            fetchChatUsers();
          },
        ),
      );
    }
    return Padding(
      padding: EdgeInsetsDirectional.only(
        top: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
        ),
      ),
      child: _buildShimmerLoader(),
    );
  }

  Widget _buildParentChatUsers({required BuildContext context}) {
    return PageView(
      controller: _pageController,
      onPageChanged: (index) {
        if (index == 0) {
          _selectedTabTitle.value = studentsKey;
        } else {
          _selectedTabTitle.value = parentsKey;
        }
      },
      children: [
        BlocBuilder<StudentChatUsersCubit, ChatUsersState>(
          builder: (context, state) {
            return _stateItemsBuilder(
              context: context,
              state: state,
              chatUsersCubit: context.read<StudentChatUsersCubit>(),
              scrollController: _studentScrollController,
            );
          },
        ),
        BlocBuilder<ParentChatUserCubit, ChatUsersState>(
          builder: (context, state) {
            return _stateItemsBuilder(
              context: context,
              state: state,
              chatUsersCubit: context.read<ParentChatUserCubit>(),
              scrollController: _parentScrollController,
            );
          },
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          _buildParentChatUsers(context: context),
          Align(alignment: Alignment.topCenter, child: _buildAppBar(context)),
        ],
      ),
    );
  }
}
