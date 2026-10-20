import 'package:eschool/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class BorderedProfilePictureContainer extends StatelessWidget {
  const BorderedProfilePictureContainer({
    required this.boxConstraints, required this.imageUrl, super.key,
    this.heightAndWidthPercentage,
    this.onTap,
  });
  final BoxConstraints boxConstraints;
  final String imageUrl;
  final Function? onTap;
  final double? heightAndWidthPercentage;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(
        boxConstraints.maxWidth *
            (heightAndWidthPercentage == null
                ? UiUtils.defaultProfilePictureHeightAndWidthPercentage * 0.5
                : heightAndWidthPercentage! * 0.5),
      ),
      onTap: () {
        onTap?.call();
      },
      child: Container(
        constraints: const BoxConstraints(maxHeight: 90, maxWidth: 90),
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: Theme.of(context).scaffoldBackgroundColor,
          ),
        ),
        width: boxConstraints.maxWidth *
            (heightAndWidthPercentage ??
                UiUtils.defaultProfilePictureHeightAndWidthPercentage),
        height: boxConstraints.maxWidth *
            (heightAndWidthPercentage ??
                UiUtils.defaultProfilePictureHeightAndWidthPercentage),
        child: CustomUserProfileImageWidget(profileUrl: imageUrl),
      ),
    );
  }
}
