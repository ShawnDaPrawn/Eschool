import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/chat/chatUserSearchCubit.dart';
import 'package:eschool/data/repositories/chatRepository.dart';
import 'package:eschool/ui/screens/chat/widget/chatUserItem.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/loadMoreErrorWidget.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/searchTextField.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
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
      builder: (_) => BlocProvider<ChatUsersSearchCubit>(
        create: (context) => ChatUsersSearchCubit(ChatRepository()),
        child: const ChatUsersSearchScreen(),
      ),
    );
  }
}

class _ChatUsersSearchScreenState extends State<ChatUsersSearchScreen> {
  final TextEditingController _searchTextController = TextEditingController();

  late final ScrollController _scrollController = ScrollController()
    ..addListener(_chatUserScrollListener);

  void _chatUserScrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<ChatUsersSearchCubit>().hasMore()) {
        context.read<ChatUsersSearchCubit>().fetchMoreChatUsers(
          isParent: context.read<AuthCubit>().isParent(),
          searchString: _searchTextController.text,
        );
      }
    }
  }

  @override
  void dispose() {
    _searchTextController.dispose();
    _scrollController.removeListener(_chatUserScrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void fetchChatUsers(String searchString) {
    context.read<ChatUsersSearchCubit>().fetchChatUsers(
      isParent: context.read<AuthCubit>().isParent(),
      searchString: searchString,
    );
  }

  Widget _buildAppBar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarBiggerHeightPercentage,
      child: Column(
        children: [
          Stack(
            children: [
              const CustomBackButton(),
              Align(
                child: Text(
                  UiUtils.getTranslatedLabel(context, searchKey),
                  style: TextStyle(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ),
            ],
          ),
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              alignment: Alignment.center,
              child: CustomSearchTextField(
                textController: _searchTextController,
                onSearch: (text) {
                  fetchChatUsers(text);
                },
                onTextClear: () {
                  context.read<ChatUsersSearchCubit>().emitInit();
                },
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
      body: Stack(
        children: [
          BlocBuilder<ChatUsersSearchCubit, ChatUsersSearchState>(
            builder: (context, state) {
              if (state is ChatUsersSearchInitial) {
                return const NoDataContainer(
                  titleKey: searchByUsernameAboveKey,
                );
              }
              if (state is ChatUsersSearchFetchSuccess) {
                return state.chatUsers.isEmpty
                    ? const NoDataContainer(titleKey: noUsersFoundKey)
                    : Padding(
                        padding: EdgeInsetsDirectional.only(
                          top: UiUtils.getScrollViewTopPadding(
                            context: context,
                            keepExtraSpace: false,
                            appBarHeightPercentage:
                                UiUtils.appBarBiggerHeightPercentage,
                          ),
                        ),
                        child: SizedBox(
                          height: double.maxFinite,
                          width: double.maxFinite,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            physics: const AlwaysScrollableScrollPhysics(),
                            child: Column(
                              children: [
                                ...List.generate(state.chatUsers.length, (
                                  index,
                                ) {
                                  final currentChatUser =
                                      state.chatUsers[index];
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
                                      context
                                          .read<ChatUsersSearchCubit>()
                                          .fetchMoreChatUsers(
                                            isParent: context
                                                .read<AuthCubit>()
                                                .isParent(),
                                            searchString:
                                                _searchTextController.text,
                                          );
                                    },
                                  ),
                                SizedBox(
                                  height: UiUtils.getScrollViewBottomPadding(
                                    context,
                                  ),
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
                    appBarHeightPercentage:
                        UiUtils.appBarBiggerHeightPercentage,
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
