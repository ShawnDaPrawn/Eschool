import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

//It will be in use when using appbar with bigger height percentage
class AppBarTitleContainer extends StatelessWidget {
  const AppBarTitleContainer({
    required this.boxConstraints, required this.title, super.key,
  });
  final BoxConstraints boxConstraints;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: SizedBox(
        width: boxConstraints.maxWidth * 0.6,
        child: Text(
          title,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Theme.of(context).scaffoldBackgroundColor,
            fontSize: UiUtils.screenTitleFontSize,
          ),
        ),
      ),
    );
  }
}
