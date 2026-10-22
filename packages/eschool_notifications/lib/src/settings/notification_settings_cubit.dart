import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/src/settings/notification_settings_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum NotificationSettingsStatus {
  loading,

  /// The server has no notification settings yet (404).
  unavailable,
  loaded,
  failure,
}

class NotificationSettingsState extends Equatable {
  const NotificationSettingsState({
    required this.status,
    this.preferences = const [],
    this.saving = const {},
    this.error,
    this.saveError,
  });

  final NotificationSettingsStatus status;
  final List<NotificationTypePreference> preferences;

  /// `type/channel` of the switches being saved.
  final Set<String> saving;

  /// Why the settings could not be loaded.
  final ApiException? error;

  /// Why the last change could not be saved (the switch went back).
  final ApiException? saveError;

  static String savingKey(String type, String channel) => '$type/$channel';

  NotificationSettingsState copyWith({
    NotificationSettingsStatus? status,
    List<NotificationTypePreference>? preferences,
    Set<String>? saving,
    ApiException? saveError,
  }) => NotificationSettingsState(
    status: status ?? this.status,
    preferences: preferences ?? this.preferences,
    saving: saving ?? this.saving,
    error: error,
    saveError: saveError,
  );

  @override
  List<Object?> get props => [status, preferences, saving, error, saveError];
}

/// The Notifications settings screen: loads the user's settings, and saves
/// a switch as soon as it is turned (shown at once, put back if the server
/// refuses).
class NotificationSettingsCubit extends Cubit<NotificationSettingsState> {
  NotificationSettingsCubit(this._repository)
    : super(
        const NotificationSettingsState(
          status: NotificationSettingsStatus.loading,
        ),
      );

  final NotificationSettingsRepository _repository;

  Future<void> load() async {
    emit(
      const NotificationSettingsState(
        status: NotificationSettingsStatus.loading,
      ),
    );
    try {
      final List<NotificationTypePreference>? preferences = await _repository
          .fetch();
      if (isClosed) {
        return;
      }
      emit(
        preferences == null
            ? const NotificationSettingsState(
                status: NotificationSettingsStatus.unavailable,
              )
            : NotificationSettingsState(
                status: NotificationSettingsStatus.loaded,
                preferences: preferences,
              ),
      );
    } catch (e) {
      if (!isClosed) {
        emit(
          NotificationSettingsState(
            status: NotificationSettingsStatus.failure,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }

  /// Turns [type] on [channel] on or off. A locked channel, or one being
  /// saved, does not change.
  Future<void> setEnabled(String type, String channel, bool enabled) async {
    final String key = NotificationSettingsState.savingKey(type, channel);
    final int index = state.preferences.indexWhere((p) => p.type == type);
    if (index < 0 || state.saving.contains(key)) {
      return;
    }
    final NotificationTypePreference before = state.preferences[index];
    final ChannelPreference? current = before.channels[channel];
    if (current == null || current.locked || current.enabled == enabled) {
      return;
    }
    emit(
      state.copyWith(
        preferences: _replace(index, before.withChannel(channel, enabled)),
        saving: {...state.saving, key},
      ),
    );
    try {
      await _repository.update(type: type, channel: channel, enabled: enabled);
      if (!isClosed) {
        emit(state.copyWith(saving: {...state.saving}..remove(key)));
      }
    } catch (e) {
      if (!isClosed) {
        final int now = state.preferences.indexWhere((p) => p.type == type);
        emit(
          state.copyWith(
            preferences: now < 0
                ? null
                : _replace(
                    now,
                    state.preferences[now].withChannel(channel, !enabled),
                  ),
            saving: {...state.saving}..remove(key),
            saveError: ApiException.fromException(e),
          ),
        );
      }
    }
  }

  List<NotificationTypePreference> _replace(
    int index,
    NotificationTypePreference preference,
  ) => [...state.preferences]..[index] = preference;
}
