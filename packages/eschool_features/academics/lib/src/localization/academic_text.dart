import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

/// The existing app locale supplies shared academic labels; tests and old
/// shells without that delegate retain the original English text.
String academicText(BuildContext context, String english) =>
    AppLocalization.of(context)?.getTranslatedValues(english) ?? english;

/// Keeps static labels const while resolving their text inside the current
/// locale, including when a screen stays mounted during a language change.
class AcademicText extends StatelessWidget {
  const AcademicText(
    this.english, {
    super.key,
    this.style,
    this.textAlign,
    this.maxLines,
    this.overflow,
    this.softWrap,
    this.semanticsLabel,
    this.textDirection,
  });
  final String english;
  final TextStyle? style;
  final TextAlign? textAlign;
  final int? maxLines;
  final TextOverflow? overflow;
  final bool? softWrap;
  final String? semanticsLabel;
  final TextDirection? textDirection;

  @override
  Widget build(BuildContext context) => Text(
    academicText(context, english),
    style: style,
    textAlign: textAlign,
    maxLines: maxLines,
    overflow: overflow,
    softWrap: softWrap,
    semanticsLabel: semanticsLabel,
    textDirection: textDirection,
  );
}
