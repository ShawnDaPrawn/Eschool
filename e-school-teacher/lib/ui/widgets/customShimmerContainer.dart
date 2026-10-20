import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
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
      height: height ?? UiUtils.shimmerLoadingContainerDefaultHeight,
      decoration: BoxDecoration(
        color: shimmerContentColor,
        borderRadius:
            customBorderRadius ?? BorderRadius.circular(borderRadius ?? 10),
      ),
    );
  }
}
