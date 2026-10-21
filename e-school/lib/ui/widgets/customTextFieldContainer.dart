import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

class CustomTextFieldContainer extends StatelessWidget {
  const CustomTextFieldContainer({
    required this.hideText,
    required this.hintTextKey,
    super.key,
    this.bottomPadding,
    this.suffixWidget,
    this.maxLength,
    this.textEditingController,
    this.maxLines,
    this.borderRadius,
    this.textAlign,
    this.keyboardType,
  });
  final String hintTextKey;
  final bool hideText;
  final double? bottomPadding;
  final Widget? suffixWidget;
  final TextEditingController? textEditingController;
  final int? maxLines;
  final double? borderRadius;
  final TextAlign? textAlign;
  final int? maxLength;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    final colorScheme = UiUtils.getColorScheme(context);
    final radius = BorderRadius.circular(borderRadius ?? AppTheme.radius);
    return Padding(
      padding: EdgeInsets.only(bottom: bottomPadding ?? 20.0),
      child: TextFormField(
        controller: textEditingController,
        maxLength: maxLength,
        obscureText: maxLines == null ? hideText : false,
        maxLines: maxLines ?? 1,
        keyboardType: keyboardType,
        textAlign: textAlign ?? TextAlign.start,
        textAlignVertical: TextAlignVertical.center,
        decoration: InputDecoration(
          counterText: '',
          contentPadding: const EdgeInsetsDirectional.fromSTEB(20, 13, 12, 13),
          suffixIcon: suffixWidget,
          hintStyle: TextStyle(
            color: colorScheme.onSurfaceVariant,
            fontWeight: FontWeight.w400,
          ),
          hintText: UiUtils.getTranslatedLabel(context, hintTextKey),
          border: OutlineInputBorder(borderRadius: radius),
          enabledBorder: OutlineInputBorder(
            borderRadius: radius,
            borderSide: BorderSide(color: colorScheme.outline),
          ),
        ),
      ),
    );
  }
}
