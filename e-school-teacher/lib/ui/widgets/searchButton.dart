import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class SearchButton extends StatelessWidget {
  const SearchButton({
    required this.onTap, super.key,
    this.edgeInsetsDirectional,
  });
  final Function onTap;
  final EdgeInsetsDirectional? edgeInsetsDirectional;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: edgeInsetsDirectional ??
          EdgeInsetsDirectional.only(
            end: UiUtils.screenContentHorizontalPadding,
          ),
      child: GestureDetector(
        onTap: () {
          onTap();
        },
        child: Container(
          decoration:
              BoxDecoration(border: Border.all(color: Colors.transparent)),
          width: 25,
          height: 25,
          child: Icon(
            CupertinoIcons.search, //Icons.calendar_month
            size: 25,
            color: Theme.of(context).scaffoldBackgroundColor,
          ),
        ),
      ),
    );
  }
}
