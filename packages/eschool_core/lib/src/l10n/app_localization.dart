import 'dart:convert';

import 'package:eschool_core/src/models/app_language.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter/services.dart';
import 'package:timeago/timeago.dart' as timeago;

//For localization of app
class AppLocalization {
  AppLocalization(this.locale);
  final Locale locale;

  //it will hold key of text and it's values in given language
  late Map<String, String> _localizedValues;

  //to access app localization instance any where in app using context
  static AppLocalization? of(BuildContext context) {
    return Localizations.of(context, AppLocalization);
  }

  //to load json(language) from assets
  Future loadJson() async {
    final String languageJsonName = locale.countryCode == null
        ? locale.languageCode
        : '${locale.languageCode}-${locale.countryCode}';
    final String jsonStringValues = await rootBundle.loadString(
      'assets/languages/$languageJsonName.json',
    );
    //value from rootbundle will be encoded string
    final Map<String, dynamic> mappedJson = json.decode(jsonStringValues);

    _localizedValues = mappedJson.map(
      (key, value) => MapEntry(key, value.toString()),
    );

    // Initialize timeago translations for current locale
    try {
      timeago.setLocaleMessages(
        locale.languageCode,
        CustomMessages(mappedJson),
      );
    } catch (e) {
      // Fallback to English messages if translation setup fails
      timeago.setLocaleMessages(locale.languageCode, timeago.EnMessages());
    }
  }

  //to get translated value of given title/key
  String? getTranslatedValues(String? key) {
    return _localizedValues[key!];
  }

  /// Turns a code from the apps' language lists (`en`, `pt-BR`) into a
  /// [Locale].
  static Locale localeFromLanguageCode(String languageCode) {
    final List<String> result = languageCode.split('-');
    return result.length == 1
        ? Locale(result.first)
        : Locale(result.first, result.last);
  }
}

/// Loads `assets/languages/<code>.json` from the app for each language in
/// [languages], the app's own list (`lib/utils/appLanguages.dart`).
class AppLocalizationDelegate extends LocalizationsDelegate<AppLocalization> {
  const AppLocalizationDelegate(this.languages);

  final List<AppLanguage> languages;

  //providing all supported languages
  @override
  bool isSupported(Locale locale) {
    return languages
        .map(
          (appLanguage) =>
              AppLocalization.localeFromLanguageCode(appLanguage.languageCode),
        )
        .toList()
        .contains(locale);
  }

  //load languageCode.json files
  @override
  Future<AppLocalization> load(Locale locale) async {
    final AppLocalization localization = AppLocalization(locale);
    await localization.loadJson();
    return localization;
  }

  @override
  bool shouldReload(LocalizationsDelegate<AppLocalization> old) {
    return false;
  }
}

/// Custom Messages from JSON for timeago
class CustomMessages implements timeago.LookupMessages {
  final Map<String, dynamic> translations;

  CustomMessages(this.translations);

  @override
  String prefixAgo() => translations['timeagoPrefixAgo'] ?? '';
  @override
  String prefixFromNow() => translations['timeagoPrefixFromNow'] ?? '';
  @override
  String suffixAgo() => translations['timeagoSuffixAgo'] ?? 'ago';
  @override
  String suffixFromNow() => translations['timeagoSuffixFromNow'] ?? 'from now';
  @override
  String lessThanOneMinute(int seconds) =>
      translations['timeagoLessThanOneMinute'] ?? 'a moment';
  @override
  String aboutAMinute(int minutes) =>
      translations['timeagoAboutAMinute'] ?? 'a minute';
  @override
  String minutes(int minutes) =>
      '$minutes ${translations['timeagoMinutes'] ?? 'minutes'}';
  @override
  String aboutAnHour(int minutes) =>
      translations['timeagoAboutAnHour'] ?? 'about an hour';
  @override
  String hours(int hours) =>
      '$hours ${translations['timeagoHours'] ?? 'hours'}';
  @override
  String aDay(int hours) => translations['timeagoADay'] ?? 'a day';
  @override
  String days(int days) => '$days ${translations['timeagoDays'] ?? 'days'}';
  @override
  String aboutAMonth(int days) =>
      translations['timeagoAboutAMonth'] ?? 'about a month';
  @override
  String months(int months) =>
      '$months ${translations['timeagoMonths'] ?? 'months'}';
  @override
  String aboutAYear(int year) =>
      translations['timeagoAboutAYear'] ?? 'about a year';
  @override
  String years(int years) =>
      '$years ${translations['timeagoYears'] ?? 'years'}';
  @override
  String wordSeparator() => translations['timeagoWordSeparator'] ?? ' ';
}
