import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

//It will store the details like contact us and other
abstract class AppSettingsState {}

class AppSettingsInitial extends AppSettingsState {}

class AppSettingsFetchInProgress extends AppSettingsState {}

class AppSettingsFetchSuccess extends AppSettingsState {

  AppSettingsFetchSuccess({required this.appSettingsResult});
  final String appSettingsResult;
}

class AppSettingsFetchFailure extends AppSettingsState {

  AppSettingsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class AppSettingsCubit extends Cubit<AppSettingsState> {

  AppSettingsCubit(this._systemRepository) : super(AppSettingsInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchAppSettings({required String type}) async {
    emit(AppSettingsFetchInProgress());

    try {
      final result = await _systemRepository.fetchSettings(type: type) ?? '';
      emit(AppSettingsFetchSuccess(appSettingsResult: result));
    } catch (e) {
      emit(AppSettingsFetchFailure(e.toString()));
    }
  }
}
