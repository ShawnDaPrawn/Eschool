import 'dart:io';

import 'package:eschool_teacher/data/models/appConfiguration.dart';
import 'package:eschool_teacher/data/models/chatSettings.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AppConfigurationState {}

class AppConfigurationInitial extends AppConfigurationState {}

class AppConfigurationFetchInProgress extends AppConfigurationState {}

class AppConfigurationFetchSuccess extends AppConfigurationState {

  AppConfigurationFetchSuccess({required this.appConfiguration});
  final AppConfiguration appConfiguration;
}

class AppConfigurationFetchFailure extends AppConfigurationState {

  AppConfigurationFetchFailure(this.errorMessage);
  final String errorMessage;
}

class AppConfigurationCubit extends Cubit<AppConfigurationState> {

  AppConfigurationCubit(this._systemRepository)
      : super(AppConfigurationInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchAppConfiguration() async {
    emit(AppConfigurationFetchInProgress());
    try {
      final appConfiguration = AppConfiguration.fromJson(
        await _systemRepository.fetchSettings(type: 'app_settings') ?? {},
      );
      emit(AppConfigurationFetchSuccess(appConfiguration: appConfiguration));
    } catch (e) {
      emit(AppConfigurationFetchFailure(e.toString()));
    }
  }

  AppConfiguration getAppConfiguration() {
    if (state is AppConfigurationFetchSuccess) {
      return (state as AppConfigurationFetchSuccess).appConfiguration;
    }
    return AppConfiguration.fromJson({});
  }

  String getAppLink() {
    if (state is AppConfigurationFetchSuccess) {
      return Platform.isIOS
          ? getAppConfiguration().iosAppLink
          : getAppConfiguration().appLink;
    }
    return '';
  }

  String getAppVersion() {
    if (state is AppConfigurationFetchSuccess) {
      return Platform.isIOS
          ? getAppConfiguration().iosAppVersion
          : getAppConfiguration().appVersion;
    }
    return '';
  }

  bool isDemoModeOn() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().isDemo;
    }
    return false;
  }

  bool appUnderMaintenance() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().appMaintenance == '1';
    }
    return false;
  }

  bool forceUpdate() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().forceAppUpdate == '1';
    }
    return false;
  }

  ChatSettings getChatSettings() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().chatSettings;
    }
    return ChatSettings.fromJson(Map.from({}));
  }

  List<String> getHolidayWeekDays() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().holidayDays;
    }
    return [];
  }
}
