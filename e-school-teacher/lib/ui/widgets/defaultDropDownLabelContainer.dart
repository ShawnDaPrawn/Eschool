import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class DefaultDropDownLabelContainer extends StatelessWidget {
  const DefaultDropDownLabelContainer({
    required this.titleLabelKey,
    required this.width,
    super.key,
    this.height,
    this.radius,
    this.margin,
    this.highlight = false,
  });
  final double? height;
  final double width;
  final double? radius;
  final String titleLabelKey;
  final EdgeInsetsGeometry? margin;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin ?? const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.symmetric(horizontal: 10),
      alignment: AlignmentDirectional.centerStart,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(radius ?? 5),
        border: Border.all(
          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
        ),
      ),
      width: width,
      height: height ?? 40,
      child: Text(
        UiUtils.getTranslatedLabel(context, titleLabelKey),
        style: TextStyle(
          fontWeight: highlight ? FontWeight.w600 : FontWeight.w400,
          fontSize: 13,
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: highlight ? 1 : 0.75),
        ),
      ),
    );
  }
}
