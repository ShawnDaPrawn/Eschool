import 'dart:async';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';
import 'practice_store.dart';
import 'practice_approval_store.dart';

bool practiceAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    current.isModuleOn('daily_practice') &&
    allowsSchoolModule(current, 'daily_practice');

/// A new reader owns its repository/cache after every fresh profile, even if
/// the profile values are equal: allocations may have changed on the server.
class PracticeSessionBoundary extends StatefulWidget {
  const PracticeSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<PracticeSessionBoundary> createState() =>
      _PracticeSessionBoundaryState();
}

class _PracticeSessionBoundaryState extends State<PracticeSessionBoundary> {
  int _generation = 0;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is MeContextLoading) _generation++;
        },
        builder: (context, state) {
          if (state is MeContextLoading) {
            return ScreenScaffold(
              title: academicText(context, 'Daily practice'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (!practiceAccessStillCurrent(widget.pinned, state.context)) {
            return ScreenScaffold(
              title: academicText(context, 'Daily practice'),
              body: const Center(
                child: AcademicText('Reopen practice to check your access.'),
              ),
            );
          }
          final epoch = _generation;
          final session = context.read<MeContextCubit>();
          return KeyedSubtree(
            key: ValueKey(epoch),
            child: Builder(
              builder: (context) => widget.builder(
                context,
                () =>
                    mounted &&
                    epoch == _generation &&
                    session.state is! MeContextLoading &&
                    practiceAccessStillCurrent(widget.pinned, session.context),
              ),
            ),
          );
        },
      );
}

/// Above all routes: known school/account, approval, role or module withdrawal
/// destroys the offline key, including when the practice route is closed.
class PracticeCacheSessionGuard extends StatelessWidget {
  const PracticeCacheSessionGuard({
    required this.child,
    this.purge = SecurePracticeStore.purge,
    this.purgeApprovals = SecurePracticeApprovalStore.purge,
    super.key,
  });
  final Widget child;
  final Future<void> Function() purge, purgeApprovals;
  Future<void> _purge() async {
    try {
      await purge();
    } catch (_) {
      /* The store always attempts key erasure. */
    }
  }

  Future<void> _purgeApprovals() async {
    try {
      await purgeApprovals();
    } catch (_) {
      /* Old scope keys remain inaccessible. */
    }
  }

  void _scopeChanged() {
    unawaited(_purge());
    unawaited(_purgeApprovals());
  }

  bool _same(MeContext? old, MeContext? next) =>
      old?.user?.id == next?.user?.id &&
      old?.currentSchoolId == next?.currentSchoolId;
  @override
  Widget build(BuildContext context) => MultiBlocListener(
    listeners: [
      BlocListener<SchoolSelectionCubit, SchoolSelectionState>(
        listenWhen: (old, next) =>
            old.selectedId != null && old.selectedId != next.selectedId,
        listener: (_, _) => _scopeChanged(),
      ),
      BlocListener<MeContextCubit, MeContextState>(
        listenWhen: (old, next) =>
            old.context != null && !_same(old.context, next.context),
        listener: (_, _) => unawaited(_purgeApprovals()),
      ),
      BlocListener<MeContextCubit, MeContextState>(
        listenWhen: (old, next) =>
            old.context != null &&
            (!_same(old.context, next.context) ||
                (next is MeContextLoaded &&
                    (!next.context.roles.contains('Student') ||
                        next.context.approval?.isApproved != true ||
                        !next.context.isModuleOn('daily_practice')))),
        listener: (_, _) => unawaited(_purge()),
      ),
    ],
    child: child,
  );
}

/// Creates a fresh scope-bound instance once per route generation, never in build.
class PracticeReaderHost extends StatefulWidget {
  const PracticeReaderHost({required this.create, super.key});
  final Widget Function() create;
  @override
  State<PracticeReaderHost> createState() => _PracticeReaderHostState();
}

class _PracticeReaderHostState extends State<PracticeReaderHost> {
  late final Widget child = widget.create();
  @override
  Widget build(BuildContext context) => child;
}
