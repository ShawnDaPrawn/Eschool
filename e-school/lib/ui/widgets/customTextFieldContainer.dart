import 'package:eschool/utils/uiUtils.dart';
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
    return Container(
      height: 50 + ((maxLines ?? 0) * 8),
      margin: EdgeInsets.only(bottom: bottomPadding ?? 20.0),
      padding: const EdgeInsetsDirectional.only(start: 20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(borderRadius ?? 10),
        border: Border.all(color: UiUtils.getColorScheme(context).secondary),
      ),
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
          suffixIcon: suffixWidget,
          hintStyle: TextStyle(
            color: UiUtils.getColorScheme(
              context,
            ).secondary.withValues(alpha: 0.4),
            fontWeight: FontWeight.w400,
          ),
          hintText: UiUtils.getTranslatedLabel(context, hintTextKey),
          border: InputBorder.none,
        ),
      ),
    );
  }
}
