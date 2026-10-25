import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

/// A role/guardian/module refresh can revoke access without switching accounts.
bool supportAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.isModuleOn('support_plans') &&
    allowsSchoolModule(current, 'support_plans');

class SupportSessionBoundary extends StatefulWidget {
  const SupportSessionBoundary({
    required this.pinned,
    this.child,
    this.builder,
    super.key,
  }) : assert((child != null) != (builder != null));
  final MeContext pinned;
  final Widget? child;
  final Widget Function(bool Function() active)? builder;
  @override
  State<SupportSessionBoundary> createState() => _SupportSessionBoundaryState();
}

class _SupportSessionBoundaryState extends State<SupportSessionBoundary> {
  int _generation = 0;
  Widget? _reader;
  bool _active(int generation) {
    if (!mounted || generation != _generation) return false;
    final state = context.read<MeContextCubit>().state;
    return state is! MeContextLoading &&
        supportAccessStillCurrent(widget.pinned, state.context);
  }

  @override
  void dispose() {
    _generation++;
    _reader = null;
    super.dispose();
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MeContextCubit, MeContextState>(
    listener: (context, state) {
      // Permissions and dated allocations may change without changing the role
      // list. Even a coalesced loading/loaded frame must recreate the reader so
      // its fresh configuration request reauthorizes the current capability.
      if (state is MeContextLoading) {
        _generation++;
        _reader = null;
      }
    },
    builder: (context, state) {
      if (state is MeContextLoading) {
        return ScreenScaffold(
          title: academicText(context, 'Support plans'),
          body: const Center(child: CircularProgressIndicator()),
        );
      }
      if (supportAccessStillCurrent(widget.pinned, state.context)) {
        final generation = _generation;
        _reader ??=
            widget.builder?.call(() => _active(generation)) ?? widget.child!;
        return KeyedSubtree(key: ValueKey(generation), child: _reader!);
      }
      _reader = null;
      return ScreenScaffold(
        title: academicText(context, 'Support plans'),
        body: Center(
          child: AcademicText(
            'Your access changed. Reopen Support plans after refreshing your account.',
          ),
        ),
      );
    },
  );
}
