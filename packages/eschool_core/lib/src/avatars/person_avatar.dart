import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool_core/src/avatars/avatar_set.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// A person's picture in a circle: their chosen avatar ([AvatarSet] key),
/// else their photo, else their initials. Used wherever a person is listed
/// (the child switcher, approvals, invitations, the avatar picker).
///
/// The picture is decorative unless [semanticsLabel] is given: the person's
/// name is normally written next to it.
class PersonAvatar extends StatelessWidget {
  const PersonAvatar({
    required this.name,
    this.avatarKey,
    this.imageUrl,
    this.radius = 20,
    this.semanticsLabel,
    super.key,
  });

  final String name;

  /// A key of [AvatarSet] (`girl_2`), drawn from the package's pictures.
  final String? avatarKey;

  /// A photo's absolute URL. A value that is an avatar key is drawn as one.
  final String? imageUrl;
  final double radius;
  final String? semanticsLabel;

  /// Up to two letters of [name], for when there is no picture.
  static String initialsOf(String name) {
    final List<String> words = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((word) => word.isNotEmpty)
        .toList();
    if (words.isEmpty) {
      return '';
    }
    final String first = words.first.characters.first;
    final String last = words.length > 1 ? words.last.characters.first : '';
    return (first + last).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final double size = radius * 2;
    final String? key = AvatarSet.isKey(avatarKey)
        ? avatarKey
        : (AvatarSet.isKey(imageUrl) ? imageUrl : null);
    final String url = (imageUrl ?? '').trim();

    final Widget initials = Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      color: colors.tertiaryContainer,
      child: Text(
        initialsOf(name),
        style: TextStyle(
          color: colors.onTertiaryContainer,
          fontWeight: FontWeight.w600,
          fontSize: radius * 0.8,
        ),
      ),
    );

    final Widget picture;
    if (key != null) {
      picture = SvgPicture.asset(
        AvatarSet.assetPath(key),
        width: size,
        height: size,
      );
    } else if (url.startsWith('http://') || url.startsWith('https://')) {
      picture = CachedNetworkImage(
        imageUrl: url,
        width: size,
        height: size,
        fit: BoxFit.cover,
        placeholder: (_, _) => initials,
        errorWidget: (_, _, _) => initials,
      );
    } else {
      picture = initials;
    }

    final Widget avatar = SizedBox.square(
      dimension: size,
      child: ClipOval(child: picture),
    );
    if (semanticsLabel == null) {
      return ExcludeSemantics(child: avatar);
    }
    return Semantics(
      label: semanticsLabel,
      image: true,
      excludeSemantics: true,
      child: avatar,
    );
  }
}
