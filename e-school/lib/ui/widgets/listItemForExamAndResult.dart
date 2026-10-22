import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class ListItemForExamAndResult extends StatelessWidget {
  const ListItemForExamAndResult({
    required this.examStartingDate,
    required this.examName,
    required this.resultGrade,
    required this.resultPercentage,
    required this.onItemTap,
    this.index,
    super.key,
  });
  final String examStartingDate;
  final String examName;
  final int? index;
  final String resultGrade;
  final double resultPercentage;
  final VoidCallback onItemTap;

  Widget _buildDetailsBackgroundContainer({
    required Widget child,
    required BuildContext context,
  }) {
    //index will be null in reports, we're showing card a little differently there
    return Center(
      child: Container(
        margin: index == null
            ? const EdgeInsets.only(bottom: 24, left: 20, right: 20)
            : const EdgeInsets.only(bottom: 30),
        width: index == null ? null : MediaQuery.sizeOf(context).width * 0.85,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        decoration: BoxDecoration(
          color: UiUtils.getColorScheme(context).surfaceContainerLow,
          borderRadius: BorderRadius.circular(10),
        ),
        child: child,
      ),
    );
  }

  TextStyle _getExamDetailsLabelTextStyle({required BuildContext context}) {
    return TextStyle(
      color: UiUtils.getColorScheme(context).onSurfaceVariant,
      fontSize: 12,
      fontWeight: FontWeight.w400,
    );
  }

  Widget _buildExamNameLabelAndDateContainer({
    required BuildContext context,
    String? examDate,
  }) {
    return Row(
      children: [
        Text(
          UiUtils.getTranslatedLabel(context, examNameKey),
          style: _getExamDetailsLabelTextStyle(context: context),
        ),
        const Spacer(),
        Text(
          '${UiUtils.getTranslatedLabel(context, dateKey)}: ',
          style: _getExamDetailsLabelTextStyle(context: context),
        ),
        Text(
          examDate == '' ? '--' : UiUtils.formatDate(DateTime.parse(examDate!)),
          style: _getExamDetailsLabelTextStyle(context: context),
        ),
      ],
    );
  }

  TextStyle _getExamNameValueTextStyle({required BuildContext context}) {
    return TextStyle(
      color: UiUtils.getColorScheme(context).onSurface,
      fontSize: 14,
      fontWeight: FontWeight.w600,
    );
  }

  Widget _buildExamNameContainer({
    required String examName,
    required BuildContext context,
  }) {
    return Text(examName, style: _getExamNameValueTextStyle(context: context));
  }

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: index == null
          ? null
          : listItemAppearanceEffects(itemIndex: index!),
      child: GestureDetector(
        onTap: onItemTap,
        child: _buildDetailsBackgroundContainer(
          context: context,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              _buildExamNameLabelAndDateContainer(
                context: context,
                examDate: examStartingDate,
              ),
              const SizedBox(height: 5),
              _buildExamNameContainer(examName: examName, context: context),
              resultGrade != '' && resultPercentage != 0
                  ? _buildResultGradeAndPercentageContainer(context: context)
                  : Container(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildResultGradeAndPercentageContainer({
    required BuildContext context,
  }) {
    return Column(
      children: [
        const Divider(),
        Row(
          children: [
            Row(
              children: [
                Text(
                  '${UiUtils.getTranslatedLabel(context, gradeKey)} - ',
                  style: _getExamNameValueTextStyle(context: context),
                ),
                Text(resultGrade),
              ],
            ),
            const Spacer(),
            Row(
              children: [
                Text(
                  '${UiUtils.getTranslatedLabel(context, percentageKey)} : ',
                  style: _getExamNameValueTextStyle(context: context),
                ),
                Text('${resultPercentage.toStringAsFixed(2)}%'),
              ],
            ),
          ],
        ),
      ],
    );
  }
}
