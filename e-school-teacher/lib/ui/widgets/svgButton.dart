import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class SvgButton extends StatelessWidget {
  const SvgButton({
    required this.onTap,
    required this.svgIconUrl,
    super.key,
    this.buttonColor,
    this.tooltip,
  });
  final Function onTap;
  final Color? buttonColor;
  final String svgIconUrl;

  /// What a screen reader announces, and what a long press shows.
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: () {
        onTap.call();
      },
      tooltip: tooltip,
      padding: EdgeInsets.zero,
      constraints: const BoxConstraints.tightFor(width: 25, height: 25),
      style: IconButton.styleFrom(
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      ),
      icon: SvgPicture.asset(
        svgIconUrl,
        width: 23,
        height: 23,
        colorFilter: ColorFilter.mode(
          buttonColor ?? Theme.of(context).colorScheme.onPrimary,
          BlendMode.srcIn,
        ),
      ),
    );
  }
}
