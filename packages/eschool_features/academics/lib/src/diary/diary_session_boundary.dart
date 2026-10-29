import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

/// The diary belongs to one approved learner at one school. Anything else
/// (another account, school or role, a withdrawn approval, the module off)
/// closes it.
bool diaryContextCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.roles.contains('Student') &&
    current.approval?.isApproved == true &&
    current.isModuleOn('diary');

/// Hosts the diary and everything it opens (editor, sheets, dialogs) in a
/// navigator of its own, so closing the diary removes all of its private
/// text at once. A routine context refresh keeps an open editor when the
/// refreshed context is still this learner's; any other change closes it.
/// While the app is not in front, the whole diary is covered.
class DiarySessionBoundary extends StatefulWidget {
  const DiarySessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<DiarySessionBoundary> createState() => _DiarySessionBoundaryState();
}

class _DiarySessionBoundaryState extends State<DiarySessionBoundary>
    with WidgetsBindingObserver {
  int _epoch = 0;
  bool _covered = false;
  GlobalKey<NavigatorState> _navigator = GlobalKey<NavigatorState>();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final state = WidgetsBinding.instance.lifecycleState;
    _covered = state != null && state != AppLifecycleState.resumed;
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Covered from `inactive` on, while frames still draw, so the app
    // switcher's snapshot shows nothing private.
    final covered = state != AppLifecycleState.resumed;
    if (covered != _covered && mounted) setState(() => _covered = covered);
  }

  bool _current(MeContextState state) =>
      diaryContextCurrent(widget.pinned, state.context);

  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (!_current(state)) {
            _epoch++;
            _navigator = GlobalKey<NavigatorState>();
          }
        },
        builder: (context, state) {
          if (!_current(state)) {
            return ScreenScaffold(
              title: academicText(context, 'My diary'),
              body: state is MeContextLoading
                  ? const Center(child: CircularProgressIndicator())
                  : const Center(
                      child: AcademicText(
                        'Reopen your diary to check your access.',
                      ),
                    ),
            );
          }
          final epoch = _epoch;
          final session = context.read<MeContextCubit>();
          final navigator = _navigator;
          bool fresh() =>
              mounted &&
              epoch == _epoch &&
              session.state is! MeContextLoading &&
              diaryContextCurrent(widget.pinned, session.context);
          return Stack(
            children: [
              KeyedSubtree(
                key: ValueKey(epoch),
                child: NavigatorPopHandler<void>(
                  onPopWithResult: (_) => navigator.currentState?.maybePop(),
                  child: Navigator(
                    key: navigator,
                    onGenerateRoute: (_) => MaterialPageRoute<void>(
                      builder: (context) => widget.builder(context, fresh),
                    ),
                  ),
                ),
              ),
              if (_covered)
                Positioned.fill(
                  child: ColoredBox(
                    key: const ValueKey('diary-privacy-cover'),
                    color: Theme.of(context).colorScheme.surface,
                    child: Center(
                      child: Icon(
                        Icons.lock_outline,
                        size: 48,
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      );
}
