import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

class ShimmerLoadingContainer extends StatelessWidget {
  const ShimmerLoadingContainer({required this.child, super.key});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: shimmerBaseColor,
      highlightColor: shimmerHighlightColor,
      child: child,
    );
  }
}
