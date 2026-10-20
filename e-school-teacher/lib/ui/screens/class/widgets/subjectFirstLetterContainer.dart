import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class SubjectFirstLetterContainer extends StatelessWidget {
  const SubjectFirstLetterContainer({required this.subjectName, super.key});
  final String subjectName;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Text(
        subjectName[0],
        style: TextStyle(
          fontSize: UiUtils.subjectFirstLetterFontSize,
          fontWeight: FontWeight.w600,
          color: Theme.of(context).scaffoldBackgroundColor,
        ),
      ),
    );
  }
}
