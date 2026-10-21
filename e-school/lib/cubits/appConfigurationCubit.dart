import 'dart:io';

import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/appConfiguration.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/paymentOptions.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';

import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AppConfigurationState extends Equatable {}

class AppConfigurationInitial extends AppConfigurationState {
  @override
  List<Object?> get props => [];
}

class AppConfigurationFetchInProgress extends AppConfigurationState {
  @override
  List<Object?> get props => [];
}

class AppConfigurationFetchSuccess extends AppConfigurationState {
  AppConfigurationFetchSuccess({required this.appConfiguration});
  final AppConfiguration appConfiguration;
  @override
  List<Object?> get props => [appConfiguration];
}

class AppConfigurationFetchFailure extends AppConfigurationState {
  AppConfigurationFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
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

  PaymentOptions getPaymentOptions() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().paymentOptions;
    }
    return PaymentOptions.fromJson(Map.from({}));
  }

  ChatSettings getChatSettings() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().chatSettings;
    }
    return ChatSettings.fromJson(Map.from({}));
  }

  bool getOnlineFeesPaymentStatus() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().isOnlineFeesPaymentEnabled == '1';
    }
    return false;
  }

  bool getIsDemoModeOn() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().isDemo;
    }
    return false;
  }

  String fetchExamRules() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().onlineExamRules;
    }
    return '';
  }

  bool isCompulsoryFeePaymentMode() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().isCompulsoryFeePaymentMode;
    }
    return false;
  }

  bool canStudentPayTheirFees() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().canStudentPayTheirFees;
    }
    return false;
  }

  List<String> getHolidayWeekDays() {
    if (state is AppConfigurationFetchSuccess) {
      return getAppConfiguration().holidayDays;
    }
    return [];
  }
}
