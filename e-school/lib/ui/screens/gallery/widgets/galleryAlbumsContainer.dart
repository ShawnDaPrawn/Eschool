import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/albumGridItemLoadingContainer.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class GalleryAlbumsContainer extends StatefulWidget {
  const GalleryAlbumsContainer({super.key});

  @override
  State<GalleryAlbumsContainer> createState() => _GalleryAlbumsContainerState();
}

class _GalleryAlbumsContainerState extends State<GalleryAlbumsContainer>
    with AutomaticKeepAliveClientMixin {
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_scrollListener);

  void _scrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<GalleryAlbumCubit>().hasMore()) {
        context.read<GalleryAlbumCubit>().fetchMoreAlbums();
      }
    }
  }

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return BlocBuilder<GalleryAlbumCubit, GalleryAlbumState>(
      builder: (context, state) {
        if (state is GalleryAlbumFetchSuccess) {
          return CustomRefreshIndicator(
            onRefreshCallback: () {
              context.read<GalleryAlbumCubit>().fetchAlbums();
            },
            displacement: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
            ),
            child: SizedBox(
              height: double.maxFinite,
              child: GridView.count(
                controller: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                crossAxisCount: 2,
                padding:
                    EdgeInsets.only(
                      top: UiUtils.getScrollViewTopPadding(
                        context: context,
                        appBarHeightPercentage:
                            UiUtils.appBarBiggerHeightPercentage,
                      ),
                      bottom: UiUtils.getScrollViewBottomPadding(context),
                    ).add(
                      EdgeInsets.symmetric(
                        horizontal: UiUtils.screenContentHorizontalPadding,
                      ),
                    ),
                mainAxisSpacing: 16,
                crossAxisSpacing: 16,
                children: List.generate(
                  state.albums.length +
                      (context.read<GalleryAlbumCubit>().hasMore() ? 1 : 0),
                  (index) {
                    if (index < state.albums.length) {
                      return GalleryAlbumContainer(album: state.albums[index]);
                    } else {
                      if (state.moreAlbumsFetchError) {
                        return ErrorContainer(
                          showErrorImage: false,
                          onTapRetry: () {
                            context.read<GalleryAlbumCubit>().fetchMoreAlbums();
                          },
                        );
                      } else {
                        return AlbumGridItemLoadingContainer();
                      }
                    }
                  },
                ),
              ),
            ),
          );
        } else if (state is GalleryAlbumFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<GalleryAlbumCubit>().fetchAlbums();
              },
            ),
          );
        } else {
          return GridView.count(
            physics: const AlwaysScrollableScrollPhysics(),
            crossAxisCount: 2,
            padding:
                EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarBiggerHeightPercentage,
                  ),
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                ).add(
                  EdgeInsets.symmetric(
                    horizontal: UiUtils.screenContentHorizontalPadding,
                  ),
                ),
            mainAxisSpacing: 16,
            crossAxisSpacing: 16,
            children: List.generate(
              6,
              (index) => AlbumGridItemLoadingContainer(),
            ),
          );
        }
      },
    );
  }

  @override
  bool get wantKeepAlive => true;
}
