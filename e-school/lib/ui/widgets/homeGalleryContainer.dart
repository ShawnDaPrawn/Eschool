import 'package:eschool/app/routes.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/galleryAlbumContainer.dart';
import 'package:eschool/ui/widgets/galleryVideoContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class HomeGalleryContainer extends StatefulWidget {
  final List<GalleryAlbum> albums;
  final List<String> videos;
  const HomeGalleryContainer({
    super.key,
    required this.albums,
    required this.videos,
  });

  @override
  State<HomeGalleryContainer> createState() => _HomeGalleryContainerState();
}

class _HomeGalleryContainerState extends State<HomeGalleryContainer> {
  // Keep track of video container keys to control playback
  final Map<int, GlobalKey<GalleryVideoContainerState>> _videoKeys = {};

  void _pauseAllVideosExcept(int currentIndex) {
    _videoKeys.forEach((index, key) {
      if (index != currentIndex) {
        key.currentState?.pauseVideo();
      }
    });
  }

  @override
  void dispose() {
    _videoKeys.clear();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final length = widget.albums.length + widget.videos.length > 10
        ? 10
        : widget.albums.length + widget.videos.length;
    if (length == 0) {
      return const SizedBox.shrink();
    }

    // Update video keys for current videos
    for (var i = 0; i < widget.videos.length; i++) {
      if (!_videoKeys.containsKey(i)) {
        _videoKeys[i] = GlobalKey<GalleryVideoContainerState>();
      }
    }

    return Column(
      children: [
        Padding(
          padding: EdgeInsets.symmetric(
            horizontal: MediaQuery.sizeOf(context).width * 0.075,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                UiUtils.getTranslatedLabel(context, galleryKey),
                style: TextStyle(
                  color: UiUtils.getColorScheme(context).onSurface,
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                ),
                textAlign: TextAlign.start,
              ),
              InkWell(
                onTap: () {
                  Navigator.pushNamed(
                    context,
                    Routes.gallery,
                    arguments: {'hasBack': true},
                  );
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
        ),
        SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
        SizedBox(
          height: MediaQuery.sizeOf(context).height * 0.2,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
            itemBuilder: (context, index) {
              if (index < widget.albums.length) {
                return SizedBox(
                  width: MediaQuery.sizeOf(context).width * 0.33,
                  child: GalleryAlbumContainer(album: widget.albums[index]),
                );
              }
              final videoIndex = index - widget.albums.length;
              return SizedBox(
                width: MediaQuery.sizeOf(context).width * 0.7,
                child: GalleryVideoContainer(
                  key: _videoKeys[videoIndex],
                  videoUrl: widget.videos[videoIndex],
                  onPlayStateChanged: () => _pauseAllVideosExcept(videoIndex),
                ),
              );
            },
            separatorBuilder: (context, index) {
              return const SizedBox(width: 16);
            },
            itemCount: length,
          ),
        ),
      ],
    );
  }
}
