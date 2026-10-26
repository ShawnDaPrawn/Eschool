import 'dart:async';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import '../localization/academic_text.dart';

/// Hide controls while preserving editors and their exact pending operations.
class MyGradePrivateAction extends StatefulWidget {
  const MyGradePrivateAction({
    required this.current,
    required this.reauthorize,
    required this.child,
    this.changes = const Stream.empty(),
    this.owner,
    super.key,
  });
  final bool Function() current;
  final Future<bool> Function() reauthorize;
  final Stream<void> changes;
  final Listenable? owner;
  final Widget child;
  @override
  State<MyGradePrivateAction> createState() => _MyGradePrivateActionState();
}

class _MyGradePrivateActionState extends State<MyGradePrivateAction>
    with WidgetsBindingObserver {
  StreamSubscription<void>? _subscription;
  bool _hidden = false, _dead = false;
  int _epoch = 0;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _subscription = widget.changes.listen((_) => _check());
    widget.owner?.addListener(_check);
  }

  void _check() {
    if (!mounted || _dead || widget.current()) return;
    _epoch++;
    setState(() => _dead = true);
  }

  @override
  void dispose() {
    _epoch++;
    _subscription?.cancel();
    widget.owner?.removeListener(_check);
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _resume();
    } else {
      _epoch++;
      if (mounted) setState(() => _hidden = true);
    }
  }

  Future<void> _resume() async {
    final epoch = ++_epoch;
    try {
      final allowed = widget.current() && await widget.reauthorize();
      if (!mounted || epoch != _epoch) return;
      setState(() {
        _dead = _dead || !allowed || !widget.current();
        _hidden = false;
      });
    } catch (_) {
      if (mounted && epoch == _epoch) {
        setState(() {
          _dead = true;
          _hidden = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_dead || !widget.current()) {
      return ScreenScaffold(
        title: academicText(context, 'My grade'),
        body: const Center(
          child: AcademicText('Reopen My grade to check your access.'),
        ),
      );
    }
    return Stack(
      children: [
        Offstage(offstage: _hidden, child: widget.child),
        if (_hidden) const Center(child: CircularProgressIndicator()),
      ],
    );
  }
}
