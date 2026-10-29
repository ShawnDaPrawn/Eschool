import 'package:flutter/material.dart';

/// ISO currency stays explicit; integer arithmetic preserves every minor unit.
String formatFinancialAmount(String currency, int cents) {
  final absolute = cents.abs();
  return '$currency ${cents < 0 ? '-' : ''}${absolute ~/ 100}.${(absolute % 100).toString().padLeft(2, '0')}';
}

/// Financial civil dates are dates, never UTC instants to shift by timezone.
String formatFinancialDateOnly(String value) {
  final match = RegExp(r'^(\d{4})-(\d{2})-(\d{2})$').firstMatch(value);
  return match == null ? value : '${match[3]}/${match[2]}/${match[1]}';
}

String formatFinancialInstant(BuildContext context, DateTime instant) {
  final local = instant.toLocal();
  final labels = MaterialLocalizations.of(context);
  return '${labels.formatMediumDate(local)} ${labels.formatTimeOfDay(TimeOfDay.fromDateTime(local), alwaysUse24HourFormat: MediaQuery.alwaysUse24HourFormatOf(context))}';
}
