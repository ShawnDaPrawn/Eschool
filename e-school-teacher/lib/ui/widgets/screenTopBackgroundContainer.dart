import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class ScreenTopBackgroundContainer extends StatelessWidget {
  const ScreenTopBackgroundContainer({
    super.key,
    this.child,
    this.heightPercentage,
    this.padding,
  });
  final Widget? child;
  final double? heightPercentage;
  final EdgeInsets? padding;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding:
          padding ??
          EdgeInsets.only(
            top:
                MediaQuery.of(context).padding.top +
                UiUtils.screenContentTopPadding,
          ),
      alignment: Alignment.topCenter,
      width: MediaQuery.sizeOf(context).width,
      height:
          MediaQuery.sizeOf(context).height *
          (heightPercentage ?? UiUtils.appBarBiggerHeightPercentage),
      decoration: BoxDecoration(
        color: UiUtils.getColorScheme(context).primary,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(25),
          bottomRight: Radius.circular(25),
        ),
      ),
      child: child,
    );
  }
}
