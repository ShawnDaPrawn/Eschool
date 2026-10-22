import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/src/settings/notification_settings_repository.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';

/// The gear in the Notifications screen's header that opens Notifications
/// settings; hidden until the server has them.
class NotificationSettingsButton extends StatelessWidget {
  const NotificationSettingsButton({
    required this.repository,
    required this.onPressed,
    super.key,
  });

  final NotificationSettingsRepository repository;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return AvailableOnly(
      featureKey: notificationSettingsFeature,
      probe: () => notificationSettingsDeployed(repository),
      child: IconButton(
        tooltip: SharedUiUtils.getTranslatedLabel(
          context,
          notificationSettingsKey,
        ),
        onPressed: onPressed,
        icon: Icon(
          Icons.settings_outlined,
          color: Theme.of(context).colorScheme.onInverseSurface,
        ),
      ),
    );
  }
}
