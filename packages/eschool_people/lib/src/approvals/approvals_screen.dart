import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/approvals/approvals_cubit.dart';
import 'package:eschool_people/src/approvals/approvals_repository.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Approvals, for a guardian: the children waiting for their approval and
/// the ones they approved. Approving shows the consent text first;
/// withdrawing asks to confirm, because the child is signed out.
class ApprovalsScreen extends StatelessWidget {
  const ApprovalsScreen({this.onChanged, super.key});

  /// Called after an approval was given or withdrawn (the app fetches
  /// `/me/context` again, whose children carry `approved`).
  final VoidCallback? onChanged;

  static Widget withRepository(
    ApprovalsRepository repository, {
    VoidCallback? onChanged,
  }) => BlocProvider<ApprovalsCubit>(
    create: (_) => ApprovalsCubit(repository)..load(),
    child: ApprovalsScreen(onChanged: onChanged),
  );

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      title: SharedUiUtils.getTranslatedLabel(context, approvalsKey),
      body: BlocConsumer<ApprovalsCubit, ApprovalsState>(
        listenWhen: (previous, current) =>
            current.actionError != null &&
            previous.actionError != current.actionError,
        listener: (context, state) =>
            showErrorSnackBar(context, state.actionError!),
        builder: (context, state) {
          switch (state.status) {
            case ApprovalsStatus.loading:
              return const Center(child: CircularProgressIndicator());
            case ApprovalsStatus.unavailable:
              return const NotAvailableYet();
            case ApprovalsStatus.failure:
              return LoadFailure(
                error: state.error!,
                onRetry: () => context.read<ApprovalsCubit>().load(),
              );
            case ApprovalsStatus.loaded:
              if (state.approvals.isEmpty) {
                return EmptyStateMessage(
                  icon: Icons.family_restroom,
                  title: SharedUiUtils.getTranslatedLabel(
                    context,
                    noApprovalsKey,
                  ),
                );
              }
              return ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                children: [
                  if (state.waiting.isNotEmpty) ...[
                    SectionHeading(
                      SharedUiUtils.getTranslatedLabel(
                        context,
                        approvalsWaitingKey,
                      ),
                    ),
                    for (final GuardianApproval approval in state.waiting)
                      _ApprovalCard(
                        approval: approval,
                        busy: state.busy.contains(approval.learner.id),
                        onChanged: onChanged,
                      ),
                  ],
                  if (state.approved.isNotEmpty) ...[
                    SectionHeading(
                      SharedUiUtils.getTranslatedLabel(
                        context,
                        approvalsGivenKey,
                      ),
                    ),
                    for (final GuardianApproval approval in state.approved)
                      _ApprovalCard(
                        approval: approval,
                        busy: state.busy.contains(approval.learner.id),
                        onChanged: onChanged,
                      ),
                  ],
                ],
              );
          }
        },
      ),
    );
  }
}

class _ApprovalCard extends StatelessWidget {
  const _ApprovalCard({
    required this.approval,
    required this.busy,
    required this.onChanged,
  });

  final GuardianApproval approval;
  final bool busy;
  final VoidCallback? onChanged;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final String studentNumber = approval.learner.studentNumber == null
        ? ''
        : '${SharedUiUtils.getTranslatedLabel(context, studentNumberLabelKey)}: '
              '${approval.learner.studentNumber}';
    final List<String> details = [
      ?approval.learner.school?.name,
      if (studentNumber.isNotEmpty) studentNumber,
      if (approval.status == GuardianApproval.withdrawn)
        SharedUiUtils.getTranslatedLabel(context, approvalWithdrawnKey),
    ];
    return Card.outlined(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ListTile(
              leading: PersonAvatar(
                name: approval.learner.name,
                imageUrl: approval.learner.image,
              ),
              title: Text(approval.learner.name),
              subtitle: details.isEmpty ? null : Text(details.join('\n')),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Align(
                alignment: AlignmentDirectional.centerEnd,
                child: approval.isApproved
                    ? OutlinedButton(
                        onPressed: busy ? null : () => _withdraw(context),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: colors.error,
                        ),
                        child: Text(
                          SharedUiUtils.getTranslatedLabel(
                            context,
                            withdrawApprovalKey,
                          ),
                          semanticsLabel:
                              '${SharedUiUtils.getTranslatedLabel(context, withdrawApprovalKey)}, ${approval.learner.name}',
                        ),
                      )
                    : FilledButton(
                        onPressed: busy ? null : () => _review(context),
                        child: Text(
                          SharedUiUtils.getTranslatedLabel(
                            context,
                            reviewAndApproveKey,
                          ),
                          semanticsLabel:
                              '${SharedUiUtils.getTranslatedLabel(context, reviewAndApproveKey)}, ${approval.learner.name}',
                        ),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Shows the consent text; approving from there.
  Future<void> _review(BuildContext context) async {
    final ApprovalsCubit cubit = context.read<ApprovalsCubit>();
    final bool? agreed = await showModalBottomSheet<bool>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (sheetContext) => _ConsentSheet(approval: approval),
    );
    if (agreed == true && await cubit.approve(approval)) {
      onChanged?.call();
    }
  }

  Future<void> _withdraw(BuildContext context) async {
    final ApprovalsCubit cubit = context.read<ApprovalsCubit>();
    final bool? confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(
          SharedUiUtils.getTranslatedLabel(
            dialogContext,
            withdrawApprovalTitleKey,
          ),
        ),
        content: Text(
          SharedUiUtils.getTranslatedLabel(
            dialogContext,
            withdrawApprovalMessageKey,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: Text(
              SharedUiUtils.getTranslatedLabel(dialogContext, cancelKey),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            style: TextButton.styleFrom(
              foregroundColor: Theme.of(dialogContext).colorScheme.error,
            ),
            child: Text(
              SharedUiUtils.getTranslatedLabel(
                dialogContext,
                withdrawApprovalKey,
              ),
            ),
          ),
        ],
      ),
    );
    if (confirmed == true && await cubit.withdraw(approval)) {
      onChanged?.call();
    }
  }
}

/// The consent text a guardian agrees to before approving.
class _ConsentSheet extends StatelessWidget {
  const _ConsentSheet({required this.approval});

  final GuardianApproval approval;

  @override
  Widget build(BuildContext context) {
    final TextTheme text = Theme.of(context).textTheme;
    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.sizeOf(context).height * 0.8,
        ),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 0, 24, 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Semantics(
                header: true,
                child: Text(
                  SharedUiUtils.getTranslatedLabel(
                    context,
                    approvalConsentTitleKey,
                  ),
                  style: text.titleLarge,
                ),
              ),
              const SizedBox(height: 4),
              Text(approval.learner.name, style: text.titleSmall),
              const SizedBox(height: 12),
              Flexible(
                child: SingleChildScrollView(
                  child: Text(approval.consent.text, style: text.bodyMedium),
                ),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () => Navigator.of(context).pop(true),
                child: Text(
                  SharedUiUtils.getTranslatedLabel(context, approveAccessKey),
                ),
              ),
              TextButton(
                onPressed: () => Navigator.of(context).pop(false),
                child: Text(
                  SharedUiUtils.getTranslatedLabel(context, cancelKey),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
