import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool transferAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    current.roles.contains('Parent') &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('school_transfers') &&
    allowsSchoolModule(current, 'school_transfers');

class TransferSessionBoundary extends StatefulWidget {
  const TransferSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(bool Function() active) builder;
  @override
  State<TransferSessionBoundary> createState() =>
      _TransferSessionBoundaryState();
}

class _TransferSessionBoundaryState extends State<TransferSessionBoundary> {
  int _generation = 0;
  Widget? _reader;
  bool _active(int generation) =>
      mounted &&
      generation == _generation &&
      context.read<MeContextCubit>().state is! MeContextLoading &&
      transferAccessStillCurrent(
        widget.pinned,
        context.read<MeContextCubit>().context,
      );
  @override
  void dispose() {
    _generation++;
    _reader = null;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (context, state) {
          if (state is MeContextLoading) {
            _generation++;
            _reader = null;
          }
        },
        builder: (context, state) {
          if (state is MeContextLoading) {
            return ScreenScaffold(
              title: academicText(context, 'School transfers'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (transferAccessStillCurrent(widget.pinned, state.context)) {
            final generation = _generation;
            _reader ??= widget.builder(() => _active(generation));
            return KeyedSubtree(key: ValueKey(generation), child: _reader!);
          }
          _reader = null;
          return ScreenScaffold(
            title: academicText(context, 'School transfers'),
            body: const Center(
              child: AcademicText(
                'Your access changed. Reopen School transfers.',
              ),
            ),
          );
        },
      );
}
