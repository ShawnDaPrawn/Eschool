import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart' as contract;
import 'package:eschool_core/eschool_core.dart';

/// The channels a notification can come by, in the order the screen shows
/// them.
abstract final class NotificationChannels {
  static const String push = 'push';
  static const String email = 'email';
  static const String inApp = 'in_app';

  static const List<String> all = [push, email, inApp];
}

/// One channel of one notification type for the signed-in user.
class ChannelPreference extends Equatable {
  const ChannelPreference({
    required this.enabled,
    this.locked = false,
    this.reason,
  });

  factory ChannelPreference.fromJson(Map<String, dynamic> json) =>
      ChannelPreference.fromContract(
        contract.NotificationChannelPreference.fromJson(json),
      );

  factory ChannelPreference.fromContract(
    contract.NotificationChannelPreference value,
  ) => ChannelPreference(
    enabled: value.enabled,
    locked: value.locked,
    reason: value.reason,
  );

  final bool enabled;

  /// The school sends this whatever the user chooses (a mandatory notice).
  final bool locked;

  /// Why it is locked, in the server's words; `null` when not given.
  final String? reason;

  ChannelPreference copyWith({bool? enabled}) => ChannelPreference(
    enabled: enabled ?? this.enabled,
    locked: locked,
    reason: reason,
  );

  @override
  List<Object?> get props => [enabled, locked, reason];
}

/// A notification type ("New notice") with the user's choice per channel.
/// A channel the school does not offer is missing from [channels].
class NotificationTypePreference extends Equatable {
  const NotificationTypePreference({
    required this.type,
    required this.label,
    required this.channels,
    this.module,
  });

  factory NotificationTypePreference.fromJson(Map<String, dynamic> json) =>
      NotificationTypePreference.fromContract(
        contract.NotificationTypePreference.fromJson(json),
      );

  factory NotificationTypePreference.fromContract(
    contract.NotificationTypePreference value,
  ) => NotificationTypePreference(
    type: value.type,
    label: value.label,
    module: value.module,
    channels: {
      if (value.channels.push != null)
        NotificationChannels.push: ChannelPreference.fromContract(
          value.channels.push!,
        ),
      if (value.channels.email != null)
        NotificationChannels.email: ChannelPreference.fromContract(
          value.channels.email!,
        ),
      if (value.channels.inApp != null)
        NotificationChannels.inApp: ChannelPreference.fromContract(
          value.channels.inApp!,
        ),
    },
  );

  /// The type's key, such as `comms.notice`.
  final String type;

  /// The type in the user's words, from the server.
  final String label;

  /// The module the type belongs to; `null` for the platform's own.
  final String? module;
  final Map<String, ChannelPreference> channels;

  /// Whether any channel is locked: the row shows a lock and the reason.
  bool get hasLock => channels.values.any((channel) => channel.locked);

  /// The first reason the server gave for a lock.
  String? get lockReason {
    for (final ChannelPreference channel in channels.values) {
      if (channel.locked && channel.reason != null) {
        return channel.reason;
      }
    }
    return null;
  }

  NotificationTypePreference withChannel(String channel, bool enabled) =>
      NotificationTypePreference(
        type: type,
        label: label,
        module: module,
        channels: {
          ...channels,
          if (channels[channel] != null)
            channel: channels[channel]!.copyWith(enabled: enabled),
        },
      );

  @override
  List<Object?> get props => [type, label, module, channels];
}

/// The signed-in user's notification settings.
abstract interface class NotificationSettingsRepository {
  /// Every type the user can be sent, or `null` when the server has no such
  /// endpoint yet (404).
  Future<List<NotificationTypePreference>?> fetch();

  /// Turns [type] on [channel] on or off. A locked one is refused (422)
  /// with the server's reason.
  Future<void> update({
    required String type,
    required String channel,
    required bool enabled,
  });
}

/// [NotificationSettingsRepository] through the app's [ApiClient].
class ApiNotificationSettingsRepository
    implements NotificationSettingsRepository {
  ApiNotificationSettingsRepository(this._client, {String? url})
    : url = url ?? '${AppConfig.apiV2Url}me/notification-preferences';

  final ApiClient _client;
  final String url;

  @override
  Future<List<NotificationTypePreference>?> fetch() async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'GET',
      url: url,
      notFoundAsNull: true,
    );
    if (result == null) {
      return null;
    }
    return contract.NotificationPreferencesResponse.fromJson(
      result,
    ).data.map(NotificationTypePreference.fromContract).toList();
  }

  @override
  Future<void> update({
    required String type,
    required String channel,
    required bool enabled,
  }) async {
    await _client.send(
      method: 'PUT',
      url: url,
      data: contract.UpdateMyNotificationPreferencesRequest(
        type: type,
        channel: contract
            .UpdateMyNotificationPreferencesRequestChannelEnum
            .values
            .firstWhere((value) => value.value == channel),
        enabled: enabled,
      ).toJson(),
    );
  }
}

/// The key of the Notifications settings screen in `EndpointAvailability`.
const String notificationSettingsFeature = 'notification-settings';

/// Whether the server has notification settings yet: the screen's entry
/// points are hidden until it does (for `AvailableOnly`).
Future<bool> notificationSettingsDeployed(
  NotificationSettingsRepository repository,
) async => await repository.fetch() != null;
