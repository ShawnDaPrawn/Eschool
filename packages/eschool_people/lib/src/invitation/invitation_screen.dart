import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:eschool_people/src/invitation/invitation_cubit.dart';
import 'package:eschool_people/src/invitation/invitation_repository.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// An invitation from a school, opened from the link in its email: the
/// school, the children, a new password when the guardian has none yet, and
/// a button that accepts and signs them in.
class InvitationScreen extends StatelessWidget {
  const InvitationScreen({required this.onAccepted, super.key});

  /// Signs the guardian in with the acceptance's answer (like a sign-in's)
  /// and moves on, as the app does after its own sign-in.
  final void Function(BuildContext context, Map<String, dynamic> signIn)
  onAccepted;

  static Widget withRepository(
    InvitationRepository repository, {
    required String token,
    required void Function(BuildContext context, Map<String, dynamic> signIn)
    onAccepted,
  }) => BlocProvider<InvitationCubit>(
    create: (_) => InvitationCubit(repository, token: token)..load(),
    child: InvitationScreen(onAccepted: onAccepted),
  );

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      title: SharedUiUtils.getTranslatedLabel(context, invitationKey),
      body: BlocConsumer<InvitationCubit, InvitationState>(
        listener: (context, state) {
          if (state.status == InvitationStatus.accepted) {
            onAccepted(context, state.signIn ?? const {});
          } else if (state.status == InvitationStatus.ready &&
              state.error != null) {
            showErrorSnackBar(context, state.error!);
          }
        },
        builder: (context, state) {
          switch (state.status) {
            case InvitationStatus.loading:
              return const Center(child: CircularProgressIndicator());
            case InvitationStatus.invalid:
              return EmptyStateMessage(
                icon: Icons.mark_email_unread_outlined,
                title: SharedUiUtils.getTranslatedLabel(
                  context,
                  invitationNotValidKey,
                ),
              );
            case InvitationStatus.failure:
              return LoadFailure(
                error: state.error!,
                onRetry: () => context.read<InvitationCubit>().load(),
              );
            case InvitationStatus.ready:
            case InvitationStatus.accepting:
            case InvitationStatus.accepted:
              return _InvitationForm(
                invitation: state.invitation!,
                busy: state.status != InvitationStatus.ready,
              );
          }
        },
      ),
    );
  }
}

class _InvitationForm extends StatefulWidget {
  const _InvitationForm({required this.invitation, required this.busy});

  final GuardianInvitation invitation;
  final bool busy;

  @override
  State<_InvitationForm> createState() => _InvitationFormState();
}

class _InvitationFormState extends State<_InvitationForm> {
  final GlobalKey<FormState> _form = GlobalKey<FormState>();
  final TextEditingController _password = TextEditingController();
  final TextEditingController _again = TextEditingController();
  bool _hidden = true;

  @override
  void dispose() {
    _password.dispose();
    _again.dispose();
    super.dispose();
  }

  void _accept() {
    if (!(_form.currentState?.validate() ?? false)) {
      return;
    }
    FocusScope.of(context).unfocus();
    context.read<InvitationCubit>().accept(password: _password.text);
  }

  @override
  Widget build(BuildContext context) {
    final GuardianInvitation invitation = widget.invitation;
    final ColorScheme colors = Theme.of(context).colorScheme;
    final TextTheme text = Theme.of(context).textTheme;
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final Widget visibility = IconButton(
      tooltip: label(_hidden ? showPasswordKey : hidePasswordKey),
      onPressed: () => setState(() => _hidden = !_hidden),
      icon: Icon(_hidden ? Icons.visibility : Icons.visibility_off),
    );
    return Form(
      key: _form,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
        children: [
          Center(child: SchoolCrest(school: invitation.school, radius: 36)),
          const SizedBox(height: 12),
          if (invitation.school != null)
            Text(
              invitation.school!.name,
              textAlign: TextAlign.center,
              style: text.titleLarge?.copyWith(color: colors.onSurface),
            ),
          const SizedBox(height: 24),
          SectionHeading(label(invitedAsParentOfKey)),
          for (final LearnerSummary learner in invitation.learners)
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: PersonAvatar(
                name: learner.name,
                imageUrl: learner.image,
              ),
              title: Text(learner.name),
            ),
          const SizedBox(height: 8),
          Text(
            '${label(invitationForKey)}: ${invitation.email}',
            style: text.bodyMedium?.copyWith(color: colors.onSurfaceVariant),
          ),
          if (invitation.expiresAt != null)
            Text(
              '${label(invitationExpiresKey)}: '
              '${formatDay(invitation.expiresAt!.toLocal())}',
              style: text.bodyMedium?.copyWith(color: colors.onSurfaceVariant),
            ),
          if (invitation.needsPassword) ...[
            const SizedBox(height: 24),
            TextFormField(
              controller: _password,
              obscureText: _hidden,
              autofillHints: const [AutofillHints.newPassword],
              textInputAction: TextInputAction.next,
              decoration: InputDecoration(
                labelText: label(createPasswordKey),
                suffixIcon: visibility,
              ),
              validator: (value) =>
                  (value ?? '').length < InvitationCubit.minimumPasswordLength
                  ? label(passwordTooShortKey)
                  : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _again,
              obscureText: _hidden,
              autofillHints: const [AutofillHints.newPassword],
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _accept(),
              decoration: InputDecoration(labelText: label(repeatPasswordKey)),
              validator: (value) => value != _password.text
                  ? label(passwordsDoNotMatchKey)
                  : null,
            ),
          ],
          const SizedBox(height: 32),
          FilledButton(
            onPressed: widget.busy ? null : _accept,
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            child: widget.busy
                ? SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: colors.primary,
                      semanticsLabel: label(acceptInvitationKey),
                    ),
                  )
                : Text(label(acceptInvitationKey)),
          ),
        ],
      ),
    );
  }
}
