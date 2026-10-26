import 'dart:async';

import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// A pushed document route retains this one-way lease from its profile owner.
class ProfileReadLease extends ChangeNotifier {
  ProfileReadLease(this._current);
  final bool Function() _current;
  bool _retired = false;
  bool get isActive => !_retired && _current();
  void retire({bool notify = true}) {
    if (_retired) return;
    _retired = true;
    if (notify) notifyListeners();
  }
}

class ProfileReadScope extends InheritedWidget {
  const ProfileReadScope({
    required this.lease,
    required super.child,
    super.key,
  });
  final ProfileReadLease lease;
  static ProfileReadLease? maybeOf(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<ProfileReadScope>()?.lease;
  @override
  bool updateShouldNotify(ProfileReadScope oldWidget) =>
      oldWidget.lease != lease;
}

/// Retires default profile forms and their pushed previews on session refresh.
/// Reopening derives new subject choices instead of reusing captured child labels.
class ProfileContextBoundary extends StatefulWidget {
  const ProfileContextBoundary({required this.builder, super.key});
  final WidgetBuilder builder;
  @override
  State<ProfileContextBoundary> createState() => _ProfileContextBoundaryState();
}

class _ProfileContextBoundaryState extends State<ProfileContextBoundary> {
  MeContextCubit? _session;
  StreamSubscription<MeContextState>? _subscription;
  MeContext? _pinned;
  ProfileReadLease? _lease;
  bool _initialized = false, _retired = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final session = context.read<MeContextCubit?>();
    if (_initialized) {
      if (!identical(session, _session)) _retire();
      return;
    }
    _initialized = true;
    _session = session;
    _bind(session?.state);
    _subscription = session?.stream.listen((state) {
      if (!mounted || _retired) return;
      if (_pinned == null && state is MeContextLoaded) {
        setState(() => _bind(state));
      } else if (_pinned != null &&
          (state is! MeContextLoaded || !identical(state.context, _pinned))) {
        _retire();
      }
    });
  }

  void _bind(MeContextState? state) {
    final pinned = state is MeContextLoaded ? state.context : null;
    if (_session != null &&
        (pinned?.user?.id == null || pinned?.currentSchoolId == null)) {
      return;
    }
    _pinned = pinned;
    final session = _session;
    _lease = ProfileReadLease(
      () =>
          mounted &&
          !_retired &&
          (session == null ||
              (session.state is MeContextLoaded &&
                  identical(session.context, pinned))),
    );
  }

  void _retire() {
    if (_retired) return;
    _retired = true;
    // Immediate zeroization precedes route transitions, including a coalesced
    // loading/loaded pair and bytes which arrive after authority changed.
    _lease?.retire();
    setState(() {});
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    });
  }

  @override
  void dispose() {
    _subscription?.cancel();
    // Children dispose their images/bytes during the same tree teardown.
    _lease?.retire(notify: false);
    _lease?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lease = _lease;
    if (lease == null || !lease.isActive) {
      return Scaffold(appBar: AppBar(), body: const SizedBox.shrink());
    }
    return ProfileReadScope(
      lease: lease,
      child: Builder(builder: widget.builder),
    );
  }
}
