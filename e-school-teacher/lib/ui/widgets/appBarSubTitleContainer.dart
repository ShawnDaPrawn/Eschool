import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

//It will be in use when using appbar with bigger height percentage
class AppBarSubTitleContainer extends StatelessWidget {
  const AppBarSubTitleContainer({
    required this.boxConstraints,
    required this.subTitle,
    super.key,
    this.topPaddingPercentage,
  });
  final String subTitle;
  final BoxConstraints boxConstraints;
  final double? topPaddingPercentage;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: Padding(
        padding: EdgeInsets.only(
          top:
              boxConstraints.maxHeight * (topPaddingPercentage ?? 0.085) +
              UiUtils.screenSubTitleFontSize,
        ),
        child: Text(
          subTitle,
          textAlign: TextAlign.center,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: UiUtils.screenSubTitleFontSize,
            color: Theme.of(context).scaffoldBackgroundColor,
          ),
        ),
      ),
    );
  }
}
