import 'package:eschool_teacher/data/repositories/settingsRepository.dart';

import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AppLocalizationState {
  AppLocalizationState(this.language);
  final Locale language;
}

class AppLocalizationCubit extends Cubit<AppLocalizationState> {
  AppLocalizationCubit(this._settingsRepository)
      : super(
          AppLocalizationState(
            UiUtils.getLocaleFromLanguageCode(
              _settingsRepository.getCurrentLanguageCode(),
            ),
          ),
        );
  final SettingsRepository _settingsRepository;

  void changeLanguage(String languageCode) {
    _settingsRepository.setCurrentLanguageCode(languageCode);
    emit(AppLocalizationState(UiUtils.getLocaleFromLanguageCode(languageCode)));
  }
}
