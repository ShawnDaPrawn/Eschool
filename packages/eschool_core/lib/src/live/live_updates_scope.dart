import 'package:flutter/widgets.dart';

import 'live_invalidation.dart';
import 'live_session.dart';

/// The authenticated session host owns the connection and replaces this scope on refresh.
class LiveUpdatesScope extends InheritedWidget {
  const LiveUpdatesScope({
    required this.events,
    this.refreshes = const Stream.empty(),
    this.session,
    required super.child,
    super.key,
  });
  final Stream<LiveInvalidation> events;
  final Stream<void> refreshes;
  final LiveSession? session;
  static Stream<void> refreshesOf(BuildContext context) =>
      context
          .dependOnInheritedWidgetOfExactType<LiveUpdatesScope>()
          ?.refreshes ??
      const Stream.empty();
  static LiveSession? sessionOf(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<LiveUpdatesScope>()?.session;
  static Stream<LiveInvalidation> eventsOf(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<LiveUpdatesScope>()?.events ??
      const Stream.empty();
  @override
  bool updateShouldNotify(LiveUpdatesScope oldWidget) =>
      events != oldWidget.events || refreshes != oldWidget.refreshes;
}
