import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool recognitionAccessStillCurrent(
  MeContext pinned,
  MeContext? current,
  String module,
) =>
    const ['house_points', 'awards'].contains(module) &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn(module) &&
    allowsSchoolModule(current, module);

class RecognitionSessionBoundary extends StatefulWidget {
  const RecognitionSessionBoundary({
    required this.pinned,
    required this.modules,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final List<String> modules;
  final Widget Function(bool Function() active) builder;
  @override
  State<RecognitionSessionBoundary> createState() =>
      _RecognitionSessionBoundaryState();
}

class _RecognitionSessionBoundaryState
    extends State<RecognitionSessionBoundary> {
  int _generation = 0;
  Widget? _reader;
  bool _active(int generation) =>
      mounted &&
      generation == _generation &&
      context.read<MeContextCubit>().state is! MeContextLoading &&
      widget.modules.isNotEmpty &&
      widget.modules.every(
        (module) => recognitionAccessStillCurrent(
          widget.pinned,
          context.read<MeContextCubit>().context,
          module,
        ),
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
              title: academicText(context, 'Houses and awards'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (widget.modules.isNotEmpty &&
              widget.modules.every(
                (module) => recognitionAccessStillCurrent(
                  widget.pinned,
                  state.context,
                  module,
                ),
              )) {
            final generation = _generation;
            _reader ??= widget.builder(() => _active(generation));
            return KeyedSubtree(key: ValueKey(generation), child: _reader!);
          }
          _reader = null;
          return ScreenScaffold(
            title: academicText(context, 'Houses and awards'),
            body: const Center(
              child: AcademicText(
                'Your recognition access changed. Reopen this screen.',
              ),
            ),
          );
        },
      );
}
