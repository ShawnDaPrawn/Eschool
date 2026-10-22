import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/src/settings/notification_settings_cubit.dart';
import 'package:eschool_notifications/src/settings/notification_settings_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Notifications settings, the same in every app: each kind of notification
/// with a switch per channel the school offers. Notices the school always
/// sends show a lock and why; their switches cannot be turned off. A switch
/// is saved as soon as it is turned.
class NotificationSettingsScreen extends StatelessWidget {
  const NotificationSettingsScreen({super.key});

  /// The screen with its cubit, fetching the settings from [repository].
  static Widget withRepository(NotificationSettingsRepository repository) =>
      BlocProvider<NotificationSettingsCubit>(
        create: (_) => NotificationSettingsCubit(repository)..load(),
        child: const NotificationSettingsScreen(),
      );

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      title: SharedUiUtils.getTranslatedLabel(context, notificationSettingsKey),
      body: BlocConsumer<NotificationSettingsCubit, NotificationSettingsState>(
        listenWhen: (previous, current) =>
            current.saveError != null &&
            previous.saveError != current.saveError,
        listener: (context, state) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                SharedUiUtils.getErrorMessageFromErrorCode(
                  context,
                  state.saveError!,
                ),
              ),
            ),
          );
        },
        builder: (context, state) {
          switch (state.status) {
            case NotificationSettingsStatus.loading:
              return const Center(child: CircularProgressIndicator());
            case NotificationSettingsStatus.unavailable:
              return EmptyStateMessage(
                icon: Icons.notifications_off_outlined,
                title: SharedUiUtils.getTranslatedLabel(
                  context,
                  notAvailableYetKey,
                ),
              );
            case NotificationSettingsStatus.failure:
              final ApiException error = state.error!;
              return Center(
                child: ErrorContainer(
                  showErrorImage: false,
                  errorMessageCode: error.isApiMessage
                      ? null
                      : error.errorMessage,
                  errorMessageText: error.isApiMessage
                      ? error.errorMessage
                      : null,
                  onTapRetry: () =>
                      context.read<NotificationSettingsCubit>().load(),
                ),
              );
            case NotificationSettingsStatus.loaded:
              if (state.preferences.isEmpty) {
                return EmptyStateMessage(
                  icon: Icons.notifications_none,
                  title: SharedUiUtils.getTranslatedLabel(
                    context,
                    nothingToSetUpYetKey,
                  ),
                );
              }
              return ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(4, 0, 4, 12),
                    child: Text(
                      SharedUiUtils.getTranslatedLabel(
                        context,
                        notificationSettingsHintKey,
                      ),
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                  for (final NotificationTypePreference preference
                      in state.preferences)
                    NotificationPreferenceCard(
                      preference: preference,
                      saving: state.saving,
                    ),
                ],
              );
          }
        },
      ),
    );
  }
}

/// One kind of notification: its name, a lock and the reason when the
/// school always sends it, and a switch per channel.
class NotificationPreferenceCard extends StatelessWidget {
  const NotificationPreferenceCard({
    required this.preference,
    required this.saving,
    super.key,
  });

  final NotificationTypePreference preference;
  final Set<String> saving;

  static String channelLabelKey(String channel) => switch (channel) {
    NotificationChannels.push => pushChannelKey,
    NotificationChannels.email => emailChannelKey,
    _ => inAppChannelKey,
  };

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final TextTheme text = Theme.of(context).textTheme;
    final String reason =
        preference.lockReason ??
        SharedUiUtils.getTranslatedLabel(context, lockedByYourSchoolKey);
    return Card.outlined(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: Row(
                children: [
                  Expanded(
                    child: Semantics(
                      header: true,
                      child: Text(
                        preference.label,
                        style: text.titleSmall?.copyWith(
                          color: colors.onSurface,
                        ),
                      ),
                    ),
                  ),
                  if (preference.hasLock)
                    Icon(Icons.lock, size: 18, color: colors.onSurfaceVariant),
                ],
              ),
            ),
            if (preference.hasLock)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
                child: ExcludeSemantics(
                  child: Text(
                    reason,
                    style: text.bodySmall?.copyWith(
                      color: colors.onSurfaceVariant,
                    ),
                  ),
                ),
              ),
            for (final String channel in NotificationChannels.all)
              if (preference.channels[channel]
                  case final ChannelPreference setting)
                _ChannelSwitch(
                  preference: preference,
                  channel: channel,
                  setting: setting,
                  reason: reason,
                  saving: saving.contains(
                    NotificationSettingsState.savingKey(
                      preference.type,
                      channel,
                    ),
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

class _ChannelSwitch extends StatelessWidget {
  const _ChannelSwitch({
    required this.preference,
    required this.channel,
    required this.setting,
    required this.reason,
    required this.saving,
  });

  final NotificationTypePreference preference;
  final String channel;
  final ChannelPreference setting;
  final String reason;
  final bool saving;

  @override
  Widget build(BuildContext context) {
    final String channelLabel = SharedUiUtils.getTranslatedLabel(
      context,
      NotificationPreferenceCard.channelLabelKey(channel),
    );
    final String spoken = [
      preference.label,
      channelLabel,
      if (setting.locked) setting.reason ?? reason,
    ].join(', ');
    return SwitchListTile(
      dense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16),
      title: Text(channelLabel, semanticsLabel: spoken),
      secondary: setting.locked
          ? const Icon(Icons.lock_outline, size: 20)
          : null,
      value: setting.enabled,
      onChanged: setting.locked || saving
          ? null
          : (enabled) => context.read<NotificationSettingsCubit>().setEnabled(
              preference.type,
              channel,
              enabled,
            ),
    );
  }
}
