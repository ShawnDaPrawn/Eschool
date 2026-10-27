import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

String feeText(BuildContext context, String english) =>
    AppLocalization.of(context)?.getTranslatedValues(english) ?? english;

String feeAmount(String currency, int cents) {
  final absolute = cents.abs();
  final decimal = (absolute % 100).toString().padLeft(2, '0');
  return '$currency ${cents < 0 ? '-' : ''}${absolute ~/ 100}.$decimal';
}

String feeDate(String value) {
  final parts = value.split('-');
  return parts.length == 3 ? '${parts[2]}/${parts[1]}/${parts[0]}' : value;
}
