import 'package:eschool/data/models/appLanguage.dart';

//by default language of the app
const String defaultLanguageCode = 'en';

//Add language code in this list
//visit this to find languageCode for your respective language
//https://developers.google.com/admin-sdk/directory/v1/languages
const List<AppLanguage> appLanguages = [
  //Please add language code here and language name
  AppLanguage(languageCode: 'en', languageName: 'English'),
  AppLanguage(languageCode: 'hi', languageName: 'हिन्दी - Hindi'),
  AppLanguage(languageCode: 'ur', languageName: 'اردو - Urdu'),
  AppLanguage(languageCode: 'tr', languageName: 'Türkçe - Turkish'),
  AppLanguage(languageCode: 'ar', languageName: 'العربية - Arabic'),
  AppLanguage(languageCode: 'es', languageName: 'Español - Spanish'),
  AppLanguage(languageCode: 'pt-BR', languageName: 'Português (Brasil) - Portuguese (Brazilian)'),
  AppLanguage(languageCode: 'fr', languageName: 'Français - French'),
  AppLanguage(languageCode: 'id', languageName: 'Bahasa Indonesia - Indonesian'),
  //
  //For example you are adding gujarati language
  //AppLanguage(languageCode: "gu", languageName: "ગુજરાતી - Gujarati"),
  //
];
