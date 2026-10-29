import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

String walletText(BuildContext context, String text) =>
    AppLocalization.of(context)?.getTranslatedValues(text) ?? text;
String walletAmount(String currency, int cents) =>
    formatFinancialAmount(currency, cents);
int? walletCents(String value) {
  if (!RegExp(r'^\d{1,10}(?:\.\d{1,2})?$').hasMatch(value)) return null;
  final parts = value.split('.');
  final cents =
      int.parse(parts[0]) * 100 +
      int.parse((parts.length == 1 ? '' : parts[1]).padRight(2, '0'));
  return cents <= 100000000000 ? cents : null;
}
