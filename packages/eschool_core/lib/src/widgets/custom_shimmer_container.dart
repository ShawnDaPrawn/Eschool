import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

class CustomShimmerContainer extends StatelessWidget {
  const CustomShimmerContainer({
    super.key,
    this.height,
    this.width,
    this.borderRadius,
    this.margin,
    this.customBorderRadius,
  });
  final double? height;
  final double? width;
  final double? borderRadius;
  final BorderRadiusGeometry? customBorderRadius;
  final EdgeInsetsGeometry? margin;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      margin: margin,
      height: height ?? SharedUiUtils.shimmerLoadingContainerDefaultHeight,
      decoration: BoxDecoration(
        color: Theme.of(
          context,
        ).colorScheme.surfaceContainerLowest.withValues(alpha: 0.85),
        borderRadius:
            customBorderRadius ?? BorderRadius.circular(borderRadius ?? 10),
      ),
    );
  }
}
