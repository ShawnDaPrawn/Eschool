import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// A section's title in a list ("Waiting for your approval").
class SectionHeading extends StatelessWidget {
  const SectionHeading(this.title, {super.key});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 8, 4, 8),
      child: Semantics(
        header: true,
        child: Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
      ),
    );
  }
}

/// What a screen shows when the server does not have its endpoint yet
/// (reached through a link while its entry points are hidden).
class NotAvailableYet extends StatelessWidget {
  const NotAvailableYet({super.key});

  @override
  Widget build(BuildContext context) => EmptyStateMessage(
    icon: Icons.hourglass_empty,
    title: SharedUiUtils.getTranslatedLabel(context, notAvailableYetKey),
  );
}

/// A failed load, with the reason and a retry.
class LoadFailure extends StatelessWidget {
  const LoadFailure({required this.error, required this.onRetry, super.key});

  final ApiException error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
    child: SingleChildScrollView(
      child: ErrorContainer(
        showErrorImage: false,
        errorMessageCode: error.isApiMessage ? null : error.errorMessage,
        errorMessageText: error.isApiMessage ? error.errorMessage : null,
        onTapRetry: onRetry,
      ),
    ),
  );
}

/// Says why an action failed, at the bottom of the screen.
void showErrorSnackBar(BuildContext context, ApiException error) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(SharedUiUtils.getErrorMessageFromErrorCode(context, error)),
    ),
  );
}

/// A day as the apps write dates: `22 Oct 2026`.
String formatDay(DateTime day) => DateFormat('d MMM yyyy').format(day);
