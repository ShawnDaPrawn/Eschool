//by default language of the app

import 'package:eschool_core/eschool_core.dart';

const String defaultLanguageCode = 'en';

//Add language code in this list
//visit this to find languageCode for your respective language
//https://developers.google.com/admin-sdk/directory/v1/languages
const List<AppLanguage> appLanguages = [
  //Please add language code here and language name
  AppLanguage(languageCode: 'en', languageName: 'English'),
  AppLanguage(languageCode: 'af', languageName: 'Afrikaans'),
  AppLanguage(languageCode: 'zu', languageName: 'isiZulu'),
  AppLanguage(languageCode: 'ur', languageName: 'اردو - Urdu'),
  AppLanguage(languageCode: 'tr', languageName: 'Türkçe - Turkish'),
  AppLanguage(languageCode: 'ar', languageName: 'العربية - Arabic'),
  AppLanguage(languageCode: 'es', languageName: 'Español - Spanish'),
  AppLanguage(
    languageCode: 'pt-BR',
    languageName: 'Português (Brasil) - Portuguese (Brazilian)',
  ),
  AppLanguage(languageCode: 'fr', languageName: 'Français - French'),
  AppLanguage(
    languageCode: 'id',
    languageName: 'Bahasa Indonesia - Indonesian',
  ),

  //
  // English is the default. To add another language, add it here and create
  // assets/languages/<code>.json with the same keys as en.json. For example:
  //AppLanguage(languageCode: 'af', languageName: 'Afrikaans'),
  //
];
