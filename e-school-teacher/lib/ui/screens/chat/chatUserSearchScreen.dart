import 'package:eschool_teacher/cubits/chat/chatUserSearchCubit.dart';
import 'package:eschool_teacher/data/repositories/chatRepository.dart';
import 'package:eschool_teacher/ui/screens/chat/widget/chatUserItem.dart';
import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/customTabBarContainer.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/loadMoreErrorWidget.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/ui/widgets/searchTextField.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChatUsersSearchScreen extends StatefulWidget {
  const ChatUsersSearchScreen({super.key});

  @override
  State<ChatUsersSearchScreen> createState() => _ChatUsersSearchScreenState();

  static CupertinoPageRoute route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<ParentChatUserSearchCubit>(
            create: (context) => ParentChatUserSearchCubit(ChatRepository()),
          ),
          BlocProvider<StudentChatUsersSearchCubit>(
            create: (context) => StudentChatUsersSearchCubit(ChatRepository()),
          ),
        ],
        child: const ChatUsersSearchScreen(),
      ),
    );
  }
}

class _ChatUsersSearchScreenState extends State<ChatUsersSearchScreen> {
  final TextEditingController _searchTextController = TextEditingController();
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
        if (context.read<ParentChatUserSearchCubit>().hasMore()) {
          context.read<ParentChatUserSearchCubit>().fetchMoreChatUsers(
            searchString: _searchTextController.text,
          );
        }
      }
    }
  }

  void _studentScrollListener() {
    if (_studentScrollController.hasClients) {
      if (_studentScrollController.offset >=
          _studentScrollController.position.maxScrollExtent) {
        if (context.read<StudentChatUsersSearchCubit>().hasMore()) {
          context.read<StudentChatUsersSearchCubit>().fetchMoreChatUsers(
            searchString: _searchTextController.text,
          );
        }
      }
    }
  }

  @override
  void dispose() {
    _studentScrollController.removeListener(_studentScrollListener);
    _parentScrollController.removeListener(_parentScrollListener);
    _parentScrollController.dispose();
    _studentScrollController.dispose();
    _pageController.dispose();
    _searchTextController.dispose();
    super.dispose();
  }

  void fetchChatUsers(String searchString) {
    context.read<ParentChatUserSearchCubit>().fetchChatUsers(
      searchString: searchString,
    );
    context.read<StudentChatUsersSearchCubit>().fetchChatUsers(
      searchString: searchString,
    );
  }

  Widget _buildAppBar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarBiggerHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return ValueListenableBuilder<String>(
            valueListenable: _selectedTabTitle,
            builder: (context, selectedTitle, _) {
              return Stack(
                children: [
                  Align(
                    alignment: Alignment.topCenter,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const SizedBox(height: 30, child: CustomBackButton()),
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 15),
                            child: CustomSearchTextField(
                              textController: _searchTextController,
                              onSearch: (text) {
                                fetchChatUsers(text);
                              },
                              onTextClear: () {
                                context
                                    .read<ParentChatUserSearchCubit>()
                                    .emitInit();
                                context
                                    .read<StudentChatUsersSearchCubit>()
                                    .emitInit();
                              },
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  AnimatedAlign(
                    curve: UiUtils.tabBackgroundContainerAnimationCurve,
                    duration: UiUtils.tabBackgroundContainerAnimationDuration,
                    alignment: selectedTitle == studentsKey
                        ? AlignmentDirectional.centerStart
                        : AlignmentDirectional.centerEnd,
                    child: TabBackgroundContainer(
                      boxConstraints: boxConstraints,
                    ),
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
                    titleKey: parentsKey,
                  ),
                ],
              );
            },
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
    required ChatUsersSearchState state,
    required ChatUsersSearchCubit chatUsersCubit,
    required ScrollController scrollController,
  }) {
    if (state is ChatUsersSearchInitial) {
      return const NoDataContainer(titleKey: searchByUsernameAboveKey);
    }
    if (state is ChatUsersSearchFetchSuccess) {
      return state.chatUsers.isEmpty
          ? const NoDataContainer(titleKey: noUsersFoundKey)
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
                          child: ChatUserItemWidget(
                            chatUser: currentChatUser,
                            showCount: false,
                          ),
                        );
                      }),
                      if (state.moreChatUserFetchProgress)
                        _buildOneChatUserShimmerLoader(),
                      if (state.moreChatUserFetchError &&
                          !state.moreChatUserFetchProgress)
                        LoadMoreErrorWidget(
                          onTapRetry: () {
                            chatUsersCubit.fetchMoreChatUsers(
                              searchString: _searchTextController.text,
                            );
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
    if (state is ChatUsersSearchFetchFailure) {
      return Center(
        child: ErrorContainer(
          errorMessageCode: state.errorMessage,
          onTapRetry: () {
            fetchChatUsers(_searchTextController.text);
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
      body: Stack(
        children: [
          PageView(
            controller: _pageController,
            onPageChanged: (index) {
              if (index == 0) {
                _selectedTabTitle.value = studentsKey;
              } else {
                _selectedTabTitle.value = parentsKey;
              }
            },
            children: [
              BlocBuilder<StudentChatUsersSearchCubit, ChatUsersSearchState>(
                builder: (context, state) {
                  return _stateItemsBuilder(
                    context: context,
                    state: state,
                    chatUsersCubit: context.read<StudentChatUsersSearchCubit>(),
                    scrollController: _studentScrollController,
                  );
                },
              ),
              BlocBuilder<ParentChatUserSearchCubit, ChatUsersSearchState>(
                builder: (context, state) {
                  return _stateItemsBuilder(
                    context: context,
                    state: state,
                    chatUsersCubit: context.read<ParentChatUserSearchCubit>(),
                    scrollController: _parentScrollController,
                  );
                },
              ),
            ],
          ),
          Align(alignment: Alignment.topCenter, child: _buildAppBar(context)),
        ],
      ),
    );
  }
}
