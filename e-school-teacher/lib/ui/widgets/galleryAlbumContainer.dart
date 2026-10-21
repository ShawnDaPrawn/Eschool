import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class GalleryAlbumContainer extends StatelessWidget {
  final GalleryAlbum album;
  const GalleryAlbumContainer({super.key, required this.album});

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: GestureDetector(
        onTap: () {
          Navigator.of(
            context,
          ).pushNamed(Routes.galleryAlbumPhotos, arguments: {'album': album});
        },
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: SizedBox(
                  child: CustomImageWidget(
                    imagePath: album.thumbnail,
                    boxFit: BoxFit.cover,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              "${album.title}\n",
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Theme.of(context).colorScheme.secondary,
                fontWeight: FontWeight.w500,
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
