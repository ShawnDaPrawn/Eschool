import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/gallery/galleryPhotosCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/studyMaterial.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customImageWidget.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoaders/albumGridItemLoadingContainer.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class GalleryAlbumPhotosScreen extends StatefulWidget {
  final GalleryAlbum album;
  const GalleryAlbumPhotosScreen({super.key, required this.album});

  @override
  State<GalleryAlbumPhotosScreen> createState() =>
      _GalleryAlbumPhotosScreenState();

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    final album = arguments['album'] as GalleryAlbum;
    return CupertinoPageRoute(
      builder: (context) => BlocProvider(
        create: (context) => GalleryAlbumPhotosCubit(album: album),
        child: GalleryAlbumPhotosScreen(album: album),
      ),
    );
  }
}

class _GalleryAlbumPhotosScreenState extends State<GalleryAlbumPhotosScreen> {
  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      context.read<GalleryAlbumPhotosCubit>().fetchAlbumPhotos();
    });
    super.initState();
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const CustomBackButton(),
          Align(
            alignment: Alignment.topCenter,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 32),
              child: Text(
                widget.album.title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onPrimary,
                  fontSize: UiUtils.screenTitleFontSize,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildImagesGridView() {
    return BlocBuilder<GalleryAlbumPhotosCubit, GalleryAlbumPhotosState>(
      builder: (context, state) {
        if (state is GalleryAlbumPhotosFetchSuccess) {
          return GridView.count(
            crossAxisCount: 2,
            padding:
                EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                  ),
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                ).add(
                  EdgeInsets.symmetric(
                    horizontal: UiUtils.screenContentHorizontalPadding,
                  ),
                ),
            mainAxisSpacing: 16,
            crossAxisSpacing: 16,
            children: List.generate(state.photos.length, (index) {
              return GestureDetector(
                onTap: () {
                  Navigator.of(context).pushNamed(
                    Routes.imageFileView,
                    arguments: {
                      'studyMaterial': StudyMaterial.fromURL(
                        state.photos[index],
                      ),
                      'multiStudyMaterial': state.photos
                          .map<StudyMaterial>((e) => StudyMaterial.fromURL(e))
                          .toList(),
                      'title': widget.album.title,
                      'initialPage': index,
                    },
                  );
                },
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: CustomImageWidget(
                    imagePath: state.photos[index],
                    boxFit: BoxFit.cover,
                  ),
                ),
              );
            }),
          );
        } else if (state is GalleryAlbumPhotosFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<GalleryAlbumPhotosCubit>().fetchAlbumPhotos();
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
                        UiUtils.appBarSmallerHeightPercentage,
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
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          _buildImagesGridView(),
          Align(alignment: Alignment.topCenter, child: _buildAppBar()),
        ],
      ),
    );
  }
}
