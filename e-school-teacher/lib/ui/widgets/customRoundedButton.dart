import 'package:flutter/material.dart';

class CustomRoundedButton extends StatelessWidget {

  //if child pass then button title will be ignored
  const CustomRoundedButton({
    required this.backgroundColor, required this.buttonTitle, required this.showBorder, super.key,
    this.widthPercentage,
    this.textAlign,
    this.borderColor,
    this.elevation,
    this.onTap,
    this.radius,
    this.shadowColor,
    this.child,
    this.height = 55,
    this.titleColor,
    this.fontWeight,
    this.textSize,
    this.alignment = Alignment.center,
    this.padding,
  });
  final String? buttonTitle;
  final double? height;
  final double? widthPercentage;
  final AlignmentGeometry? alignment;
  final EdgeInsets? padding;
  final Function? onTap;
  final Color backgroundColor;
  final TextAlign? textAlign;
  final double? radius;
  final Color? shadowColor;
  final bool showBorder;
  final Color? borderColor;
  final Color? titleColor;
  final double? textSize;
  final FontWeight? fontWeight;
  final double? elevation;
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    return Material(
      shadowColor: shadowColor ?? Colors.black54,
      elevation: elevation ?? 0.0,
      color: backgroundColor,
      borderRadius: BorderRadius.circular(radius ?? 10.0),
      child: InkWell(
        borderRadius: BorderRadius.circular(radius ?? 10.0),
        onTap: onTap as void Function()?,
        child: Container(
          padding: padding ?? const EdgeInsets.symmetric(horizontal: 15), //
          alignment: alignment,
          height: height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(radius ?? 10.0),
            border: showBorder
                ? Border.all(
                    color:
                        borderColor ??
                        Theme.of(context).scaffoldBackgroundColor,
                  )
                : null,
          ),
          width: widthPercentage != null
              ? MediaQuery.sizeOf(context).width * widthPercentage!
              : null,
          child:
              child ??
              Text(
                '$buttonTitle',
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textAlign: textAlign,
                style: TextStyle(
                  fontSize: textSize ?? 18.0,
                  color:
                      titleColor ?? Theme.of(context).scaffoldBackgroundColor,
                  fontWeight: fontWeight ?? FontWeight.normal,
                ),
              ),
        ),
      ),
    );
  }
}
