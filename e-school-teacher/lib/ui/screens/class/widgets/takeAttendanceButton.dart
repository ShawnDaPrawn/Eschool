import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

/// The class page's floating button that opens the register. It shows only
/// the icon; screen readers read its name.
class TakeAttendanceButton extends StatelessWidget {
  const TakeAttendanceButton({required this.onPressed, super.key});

  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton(
      onPressed: onPressed,
      backgroundColor: Theme.of(context).colorScheme.primary,
      child: Semantics(
        label: UiUtils.getTranslatedLabel(context, takeAttendanceKey),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: SvgPicture.asset(
            Assets.takeAttendance,
            excludeFromSemantics: true,
            colorFilter: ColorFilter.mode(
              Theme.of(context).colorScheme.onPrimary,
              BlendMode.srcIn,
            ),
          ),
        ),
      ),
    );
  }
}
