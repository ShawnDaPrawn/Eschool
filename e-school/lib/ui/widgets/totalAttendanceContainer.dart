import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class TotalAttendanceContainer extends StatelessWidget {
  final num totalPresent;
  final num totalAbsent;

  const TotalAttendanceContainer({
    super.key,
    required this.totalPresent,
    required this.totalAbsent,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.inverseSurface,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildDetail(context, totalPresentKey, totalPresent.toString()),
          Container(
            height: 72,
            width: 1,
            color: Theme.of(context).colorScheme.onInverseSurface,
          ),
          _buildDetail(context, totalAbsentKey, totalAbsent.toString()),
        ],
      ),
    );
  }

  Widget _buildDetail(BuildContext context, String titleKey, String value) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          UiUtils.getTranslatedLabel(context, titleKey),
          style: TextStyle(
            color: Theme.of(context).colorScheme.onInverseSurface,
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 10),
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.onInverseSurface,
            shape: BoxShape.circle,
          ),
          alignment: Alignment.center,
          child: Text(
            value,
            style: TextStyle(
              color: Theme.of(context).colorScheme.inverseSurface,
              fontWeight: FontWeight.bold,
              fontSize: 16,
            ),
          ),
        ),
      ],
    );
  }
}
