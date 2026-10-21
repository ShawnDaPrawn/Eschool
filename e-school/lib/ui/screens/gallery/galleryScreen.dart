import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/screens/gallery/widgets/galleryAlbumsContainer.dart';
import 'package:eschool/ui/widgets/customTabBarContainer.dart';
import 'package:eschool/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class GalleryScreen extends StatefulWidget {
  const GalleryScreen({super.key});

  @override
  State<GalleryScreen> createState() => _GalleryScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(builder: (_) => const GalleryScreen());
  }
}

class _GalleryScreenState extends State<GalleryScreen> {
  late String _selectedTabTitle = albumsKey;

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.read<GalleryAlbumCubit>().state
          is! GalleryAlbumPhotosFetchSuccess) {
        context.read<GalleryAlbumCubit>().fetchAlbums();
      }
      if (context.read<GalleryVideosCubit>().state
          is! GalleryVideosFetchSuccess) {
        context.read<GalleryVideosCubit>().fetchVideos();
      }
    });
    super.initState();
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              const CustomBackButton(),
              Align(
                alignment: Alignment.topCenter,
                child: Text(
                  UiUtils.getTranslatedLabel(context, galleryKey),
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onInverseSurface,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ),
              AnimatedAlign(
                curve: UiUtils.tabBackgroundContainerAnimationCurve,
                duration: UiUtils.tabBackgroundContainerAnimationDuration,
                alignment: _selectedTabTitle == albumsKey
                    ? AlignmentDirectional.centerStart
                    : AlignmentDirectional.centerEnd,
                child: TabBarBackgroundContainer(
                  boxConstraints: boxConstraints,
                ),
              ),
              CustomTabBarContainer(
                boxConstraints: boxConstraints,
                alignment: AlignmentDirectional.centerStart,
                isSelected: _selectedTabTitle == albumsKey,
                onTap: () {
                  setState(() {
                    _selectedTabTitle = albumsKey;
                  });
                },
                titleKey: albumsKey,
              ),
              CustomTabBarContainer(
                boxConstraints: boxConstraints,
                alignment: AlignmentDirectional.centerEnd,
                isSelected: _selectedTabTitle == videosKey,
                onTap: () {
                  setState(() {
                    _selectedTabTitle = videosKey;
                  });
                },
                titleKey: videosKey,
              ),
            ],
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
          Align(
            alignment: Alignment.topCenter,
            child: CustomRefreshIndicator(
              onRefreshCallback: () {
                if (_selectedTabTitle == albumsKey) {
                  context.read<GalleryAlbumCubit>().fetchAlbums();
                } else {
                  context.read<GalleryVideosCubit>().fetchVideos();
                }
              },
              displacement: UiUtils.getScrollViewTopPadding(
                context: context,
                appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
              ),
              child: SizedBox(
                height: double.maxFinite,
                child: IndexedStack(
                  index: _selectedTabTitle == albumsKey ? 0 : 1,
                  children: [
                    const GalleryAlbumsContainer(),
                    const GalleryVideosContainer(),
                  ],
                ),
              ),
            ),
          ),
          Align(alignment: Alignment.topCenter, child: _buildAppBar()),
        ],
      ),
    );
  }
}
