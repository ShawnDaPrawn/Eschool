import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

String feeText(BuildContext context, String english) =>
    AppLocalization.of(context)?.getTranslatedValues(english) ?? english;

String feeAmount(String currency, int cents) =>
    formatFinancialAmount(currency, cents);

String feeDate(String value) => formatFinancialDateOnly(value);
