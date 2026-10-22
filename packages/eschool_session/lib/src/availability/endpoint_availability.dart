import 'package:flutter/widgets.dart';

/// Whether the server has a feature's endpoint yet. A new screen's entry
/// point stays hidden while its endpoint answers 404 (not deployed), so the
/// apps can ship ahead of the server. Each answer is asked once and kept
/// until [forget] (at sign-out and on changing school); a failed check
/// (no network) is not kept, so it is asked again next time.
// ignore: avoid_classes_with_only_static_members
abstract final class EndpointAvailability {
  static final Map<String, Future<bool>> _known = {};

  /// Whether the feature [key] is there, asking [probe] the first time.
  static Future<bool> check(String key, Future<bool> Function() probe) {
    return _known[key] ??= probe().catchError((Object _) {
      _known.remove(key);
      return false;
    });
  }

  /// Forgets every answer.
  static void forget() => _known.clear();
}

/// Shows [child] only when the feature [featureKey] is on the server
/// ([EndpointAvailability]); nothing (and no room) otherwise.
class AvailableOnly extends StatelessWidget {
  const AvailableOnly({
    required this.featureKey,
    required this.probe,
    required this.child,
    this.enabled = true,
    super.key,
  });

  final String featureKey;
  final Future<bool> Function() probe;
  final Widget child;

  /// False hides [child] without asking (the feature is not for this user).
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    if (!enabled) {
      return const SizedBox.shrink();
    }
    return FutureBuilder<bool>(
      future: EndpointAvailability.check(featureKey, probe),
      builder: (context, snapshot) =>
          snapshot.data == true ? child : const SizedBox.shrink(),
    );
  }
}
