import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class GalleryVideosContainer extends StatefulWidget {
  const GalleryVideosContainer({super.key});

  @override
  State<GalleryVideosContainer> createState() => _GalleryVideosContainerState();
}

class _GalleryVideosContainerState extends State<GalleryVideosContainer>
    with AutomaticKeepAliveClientMixin {
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_scrollListener);

  // Keep track of video container keys to control playback
  final Map<int, GlobalKey<GalleryVideoContainerState>> _videoKeys = {};

  void _scrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<GalleryVideosCubit>().hasMore()) {
        context.read<GalleryVideosCubit>().fetchMoreVideos();
      }
    }
  }

  void _pauseAllVideosExcept(int currentIndex) {
    _videoKeys.forEach((index, key) {
      if (index != currentIndex) {
        key.currentState?.pauseVideo();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    _videoKeys.clear();
    super.dispose();
  }

  Widget _buildVideoLoader() {
    return ShimmerLoadingContainer(
      child: CustomShimmerContainer(
        borderRadius: 12,
        width: (MediaQuery.of(context).size.width) - 36,
        height: (MediaQuery.of(context).size.height * .2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return BlocBuilder<GalleryVideosCubit, GalleryVideosState>(
      builder: (context, state) {
        if (state is GalleryVideosFetchSuccess) {
          // Update video keys for current videos
          for (var i = 0; i < state.videos.length; i++) {
            if (!_videoKeys.containsKey(i)) {
              _videoKeys[i] = GlobalKey<GalleryVideoContainerState>();
            }
          }

          return CustomRefreshIndicator(
            onRefreshCallback: () {
              context.read<GalleryVideosCubit>().fetchVideos();
            },
            displacement: SharedUiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage:
                  SharedUiUtils.appBarBiggerHeightPercentage,
            ),
            child: SizedBox(
              height: double.maxFinite,
              child: ListView.separated(
                controller: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                padding:
                    EdgeInsets.only(
                      top: SharedUiUtils.getScrollViewTopPadding(
                        context: context,
                        appBarHeightPercentage:
                            SharedUiUtils.appBarBiggerHeightPercentage,
                      ),
                      bottom: SharedUiUtils.getScrollViewBottomPadding(context),
                    ).add(
                      EdgeInsets.symmetric(
                        horizontal:
                            SharedUiUtils.screenContentHorizontalPadding,
                      ),
                    ),
                separatorBuilder: (context, index) {
                  return const SizedBox(height: 16);
                },
                itemCount:
                    state.videos.length +
                    (context.read<GalleryVideosCubit>().hasMore() ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index < state.videos.length) {
                    return SizedBox(
                      width: double.maxFinite,
                      height: MediaQuery.of(context).size.height * 0.2,
                      child: GalleryVideoContainer(
                        key: _videoKeys[index],
                        videoUrl: state.videos[index],
                        onPlayStateChanged: () => _pauseAllVideosExcept(index),
                      ),
                    );
                  } else {
                    if (state.moreVideosFetchError) {
                      return ErrorContainer(
                        showErrorImage: false,
                        onTapRetry: () {
                          context.read<GalleryVideosCubit>().fetchMoreVideos();
                        },
                      );
                    } else {
                      return _buildVideoLoader();
                    }
                  }
                },
              ),
            ),
          );
        } else if (state is GalleryVideosFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<GalleryVideosCubit>().fetchVideos();
              },
            ),
          );
        } else {
          return ListView.separated(
            physics: const AlwaysScrollableScrollPhysics(),
            separatorBuilder: (context, index) {
              return const SizedBox(height: 16);
            },
            itemCount: 4,
            itemBuilder: (context, index) {
              return _buildVideoLoader();
            },
            padding:
                EdgeInsets.only(
                  top: SharedUiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        SharedUiUtils.appBarBiggerHeightPercentage,
                  ),
                  bottom: SharedUiUtils.getScrollViewBottomPadding(context),
                ).add(
                  EdgeInsets.symmetric(
                    horizontal: SharedUiUtils.screenContentHorizontalPadding,
                  ),
                ),
          );
        }
      },
    );
  }

  @override
  bool get wantKeepAlive => true;
}
