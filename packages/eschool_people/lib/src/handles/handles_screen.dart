import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:eschool_people/src/handles/handles_cubit.dart';
import 'package:eschool_people/src/handles/handles_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

class HandlesScreen extends StatefulWidget {
  const HandlesScreen({this.onChanged, super.key});
  final VoidCallback? onChanged;

  static Widget withRepository(
    HandlesRepository repository, {
    required bool guardian,
    VoidCallback? onChanged,
  }) => BlocProvider(
    create: (_) => HandlesCubit(repository, guardian: guardian)..load(),
    child: HandlesScreen(onChanged: onChanged),
  );

  @override
  State<HandlesScreen> createState() => _HandlesScreenState();
}

class _HandlesScreenState extends State<HandlesScreen> {
  final controller = TextEditingController();
  final form = GlobalKey<FormState>();
  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);

  Future<void> _choose() async {
    if (!form.currentState!.validate()) return;
    if (await context.read<HandlesCubit>().choose(
          controller.text.trim().toLowerCase(),
        ) &&
        mounted) {
      controller.clear();
      widget.onChanged?.call();
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(label(handleSavedKey))));
    }
  }

  Future<void> _decide(int id, bool approve) async {
    if (await context.read<HandlesCubit>().decide(id, approve: approve) &&
        mounted) {
      widget.onChanged?.call();
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(label(handleSettingsKey))),
    body: BlocConsumer<HandlesCubit, HandlesState>(
      listener: (context, state) {
        if (state.error != null && state.status == HandlesStatus.loaded) {
          showErrorSnackBar(context, state.error!);
        }
      },
      builder: (context, state) {
        if (state.status == HandlesStatus.loading) {
          return const Center(child: CircularProgressIndicator());
        }
        if (state.status == HandlesStatus.unavailable) {
          return const NotAvailableYet();
        }
        if (state.status == HandlesStatus.failure) {
          return LoadFailure(
            error: state.error!,
            onRetry: context.read<HandlesCubit>().load,
          );
        }
        final value = state.value!;
        return RefreshIndicator(
          onRefresh: context.read<HandlesCubit>().load,
          child: ListView(
            padding: const EdgeInsets.all(16),
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              if (value.handle != null)
                Text(
                  '@${value.handle}',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
              Text(label(handleRulesKey)),
              if (value.proposal != null)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Text(
                    '${label(handlePendingKey)}: @${value.proposal!.handle}',
                  ),
                ),
              if (value.canChangeAfter != null)
                Text(
                  '${label(handleChangeAfterKey)}: ${DateFormat.yMMMd().format(value.canChangeAfter!)}',
                ),
              Form(
                key: form,
                child: Column(
                  children: [
                    TextFormField(
                      controller: controller,
                      maxLength: 20,
                      autocorrect: false,
                      textCapitalization: TextCapitalization.none,
                      decoration: InputDecoration(
                        labelText: label(handleLabelKey),
                      ),
                      validator: (value) =>
                          RegExp(
                            r'^[a-z][a-z0-9._]{1,18}[a-z0-9]$',
                          ).hasMatch((value ?? '').trim().toLowerCase())
                          ? null
                          : label(handleRulesKey),
                    ),
                    FilledButton(
                      onPressed: state.busy ? null : _choose,
                      child: Text(label(handleChooseKey)),
                    ),
                  ],
                ),
              ),
              if (context.read<HandlesCubit>().guardian) ...[
                SectionHeading(label(handleRequestsKey)),
                if (state.requestsError != null)
                  Text(
                    state.requestsError!.errorMessage ==
                            'Verify your email to review handle requests.'
                        ? label(handleVerifyEmailKey)
                        : SharedUiUtils.getErrorMessageFromErrorCode(
                            context,
                            state.requestsError!,
                          ),
                  ),
                if (state.requests.isEmpty && state.requestsError == null)
                  Text(label(handleNoRequestsKey)),
                for (final request in state.requests)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            request.learner.name,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text('@${request.handle}'),
                          Wrap(
                            spacing: 8,
                            children: [
                              FilledButton(
                                onPressed: state.busy
                                    ? null
                                    : () => _decide(request.id, true),
                                child: Text(label(handleApproveKey)),
                              ),
                              TextButton(
                                onPressed: state.busy
                                    ? null
                                    : () => _decide(request.id, false),
                                child: Text(label(handleRejectKey)),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ],
          ),
        );
      },
    ),
  );
}
