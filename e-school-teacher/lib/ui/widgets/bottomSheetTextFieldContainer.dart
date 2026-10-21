import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class BottomSheetTextFieldContainer extends StatelessWidget {
  const BottomSheetTextFieldContainer({
    required this.hintText,
    required this.maxLines,
    required this.textEditingController,
    super.key,
    this.textInputFormatter,
    this.suffix,
    this.maxlength,
    this.hideText,
    this.keyboardType,
    this.margin,
    this.contentPadding,
    this.disabled = false,
  });
  final String hintText;
  final TextEditingController textEditingController;
  final EdgeInsetsGeometry? contentPadding;
  final int? maxLines;
  final List<TextInputFormatter>? textInputFormatter;
  final TextInputType? keyboardType;
  final EdgeInsetsGeometry? margin;
  final bool? hideText;
  final Widget? suffix;
  final int? maxlength;
  final bool disabled;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    // A disabled field here is a read-only picker (tapping it opens a date
    // picker), so it keeps the enabled outline.
    final outline = OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppTheme.radius),
      borderSide: BorderSide(color: colorScheme.outline),
    );
    return Padding(
      padding: margin ?? EdgeInsets.zero,
      child: TextField(
        enabled: !disabled,
        obscureText: hideText ?? false,
        keyboardType: keyboardType,
        controller: textEditingController,
        style: TextStyle(
          color: colorScheme.onSurface,
          fontSize: UiUtils.textFieldFontSize,
        ),
        maxLines: maxLines,
        inputFormatters: textInputFormatter,
        decoration: InputDecoration(
          filled: true,
          fillColor: colorScheme.surfaceContainerLow,
          contentPadding: const EdgeInsetsDirectional.only(
            top: 12,
            end: 12,
            bottom: 12,
          ).add(contentPadding ?? const EdgeInsetsDirectional.only(start: 20)),
          suffixIcon: suffix,
          hintText: hintText,
          hintStyle: TextStyle(
            color: colorScheme.onSurfaceVariant,
            fontSize: UiUtils.textFieldFontSize,
          ),
          enabledBorder: outline,
          disabledBorder: outline,
        ),
        maxLength: maxlength,
      ),
    );
  }
}
