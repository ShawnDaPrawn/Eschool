import 'dart:async';
import 'package:flutter/widgets.dart';
import '../api/api_client.dart';
import 'live_session.dart';
import 'live_updates_scope.dart';

/// Lifecycle and authorization-generation owner above the authenticated Navigator.
class LiveUpdatesHost extends StatefulWidget {
  const LiveUpdatesHost({
    required this.client,
    required this.schoolId,
    required this.accountId,
    required this.enabled,
    required this.contextActive,
    required this.authorizationKey,
    required this.child,
    this.origin = 'https://app.navigateed.co.za',
    super.key,
  });
  final ApiClient client;
  final int schoolId, accountId;
  final bool enabled;
  final Object authorizationKey;
  final bool Function() contextActive;
  final String origin;
  final Widget child;
  @override
  State<LiveUpdatesHost> createState() => _LiveUpdatesHostState();
}

class _LiveUpdatesHostState extends State<LiveUpdatesHost>
    with WidgetsBindingObserver {
  LiveSession? _session;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  void _create() {
    if (!widget.enabled) return;
    _session = LiveSession(
      client: widget.client,
      schoolId: widget.schoolId,
      accountId: widget.accountId,
      contextActive: widget.contextActive,
      origin: widget.origin,
    );
    final state = WidgetsBinding.instance.lifecycleState;
    if (state == null || state == AppLifecycleState.resumed) {
      unawaited(_session!.resume());
    }
  }

  @override
  void didUpdateWidget(LiveUpdatesHost oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.client != widget.client ||
        oldWidget.schoolId != widget.schoolId ||
        oldWidget.accountId != widget.accountId ||
        oldWidget.enabled != widget.enabled ||
        oldWidget.authorizationKey != widget.authorizationKey) {
      unawaited(_session?.dispose());
      _session = null;
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      unawaited(_session?.resume());
    } else {
      _session?.pause();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    unawaited(_session?.dispose());
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => LiveUpdatesScope(
    events: _session?.events ?? const Stream.empty(),
    refreshes: _session?.refreshes ?? const Stream.empty(),
    session: _session,
    child: widget.child,
  );
}
