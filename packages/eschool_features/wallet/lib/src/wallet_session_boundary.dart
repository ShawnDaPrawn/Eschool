import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'wallet_text.dart';

bool walletAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    current.roles.any(
      ['Parent', 'Student', 'Teacher', 'Facilitator', 'Super Admin'].contains,
    ) &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('wallet') &&
    current.isModuleOn('vendors') == pinned.isModuleOn('vendors') &&
    current.isModuleOn('ticketing') == pinned.isModuleOn('ticketing') &&
    current.isModuleOn('wallet_payments') ==
        pinned.isModuleOn('wallet_payments') &&
    current.isModuleReadOnly('wallet_payments') ==
        pinned.isModuleReadOnly('wallet_payments') &&
    current.isModuleReadOnly('ticketing') ==
        pinned.isModuleReadOnly('ticketing') &&
    current.isModuleReadOnly('vendors') == pinned.isModuleReadOnly('vendors') &&
    allowsSchoolModule(current, 'wallet') &&
    current.isModuleReadOnly('wallet') == pinned.isModuleReadOnly('wallet');

class WalletSessionBoundary extends StatefulWidget {
  const WalletSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext context, bool Function() active) builder;

  @override
  State<WalletSessionBoundary> createState() => _WalletSessionBoundaryState();
}

class _WalletSessionBoundaryState extends State<WalletSessionBoundary> {
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
              !walletAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              walletAccessStillCurrent(
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
            title: walletText(context, 'Wallets'),
            body: Center(
              child: state is MeContextLoading
                  ? const CircularProgressIndicator()
                  : Text(
                      walletText(
                        context,
                        'Your wallet access changed. Reopen Wallets.',
                      ),
                    ),
            ),
          );
        },
      );
}
