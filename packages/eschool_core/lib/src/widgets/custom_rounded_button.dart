import 'package:eschool_core/src/theme/app_theme.dart';
import 'package:flutter/material.dart';

/// The apps' standard button: a Material 3 [FilledButton] that keeps the
/// sizing rules this widget has always had. Without [widthPercentage] it fills
/// the width it is given; [height] defaults to 55.
///
/// A null [onTap] keeps the button's colours, because several screens use it
/// as a label or swap [child] for a progress indicator while saving.
class CustomRoundedButton extends StatelessWidget {
  //if child pass then button title will be ignored
  const CustomRoundedButton({
    required this.backgroundColor,
    required this.buttonTitle,
    required this.showBorder,
    super.key,
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
    final colorScheme = Theme.of(context).colorScheme;
    final foregroundColor = titleColor ?? colorScheme.onPrimary;

    return FilledButton(
      onPressed: onTap == null ? null : () => onTap!(),
      style: FilledButton.styleFrom(
        backgroundColor: backgroundColor,
        foregroundColor: foregroundColor,
        disabledBackgroundColor: backgroundColor,
        disabledForegroundColor: foregroundColor,
        elevation: elevation ?? 0,
        shadowColor: shadowColor ?? colorScheme.shadow,
        padding: EdgeInsets.zero,
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        iconSize: IconTheme.of(context).size,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radius ?? AppTheme.radius),
          side: showBorder
              ? BorderSide(color: borderColor ?? colorScheme.onPrimary)
              : BorderSide.none,
        ),
      ),
      // The Container sizes the button: it fills the space it is given unless
      // a width or a null alignment says otherwise.
      child: Container(
        padding: padding ?? const EdgeInsets.symmetric(horizontal: 15),
        alignment: alignment,
        height: height,
        width: widthPercentage != null
            ? MediaQuery.sizeOf(context).width * widthPercentage!
            : null,
        child:
            child ??
            Text(
              '$buttonTitle',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: textAlign ?? TextAlign.center,
              style: TextStyle(
                fontSize: textSize ?? 18.0,
                fontWeight: fontWeight ?? FontWeight.normal,
              ),
            ),
      ),
    );
  }
}
