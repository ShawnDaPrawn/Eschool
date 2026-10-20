import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool_teacher/utils/assets.dart';

import 'package:flutter/cupertino.dart';
import 'package:flutter_svg/svg.dart';

//This widget will return cuticular or rectangular profile image or default image on error with cached network image for general usage
class CustomUserProfileImageWidget extends StatelessWidget {
  const CustomUserProfileImageWidget({
    required this.profileUrl, super.key,
    this.color,
    this.radius,
  });
  final String profileUrl;
  final Color? color;
  final BorderRadius? radius;

  CachedNetworkImage _imageOrDefaultProfileImage() {
    return CachedNetworkImage(
      fit: BoxFit.cover,
      imageUrl: profileUrl,
      errorWidget: (context, url, error) {
        return SvgPicture.asset(
          Assets.defaultProfile,
          colorFilter: color == null
              ? null
              : ColorFilter.mode(color!, BlendMode.srcIn),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return radius != null
        ? ClipRRect(borderRadius: radius!, child: _imageOrDefaultProfileImage())
        : ClipOval(child: _imageOrDefaultProfileImage());
  }
}
