import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'fee_text.dart';

bool feeAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    current.roles.contains('Parent') &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('fees') &&
    allowsSchoolModule(current, 'fees') &&
    current.isModuleReadOnly('fees') == pinned.isModuleReadOnly('fees');

class FeeSessionBoundary extends StatefulWidget {
  const FeeSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext context, bool Function() active) builder;

  @override
  State<FeeSessionBoundary> createState() => _FeeSessionBoundaryState();
}

class _FeeSessionBoundaryState extends State<FeeSessionBoundary> {
  int _generation = 0;

  @override
  void dispose() {
    _generation++;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is! MeContextLoaded ||
              !feeAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              feeAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          if (active()) {
            return KeyedSubtree(
              key: ValueKey(generation),
              child: widget.builder(context, active),
            );
          }
          return ScreenScaffold(
            title: feeText(context, 'Fee statements'),
            body: Center(
              child: state is MeContextLoading
                  ? const CircularProgressIndicator()
                  : Text(
                      feeText(
                        context,
                        'Your fee access changed. Reopen this screen.',
                      ),
                    ),
            ),
          );
        },
      );
}
