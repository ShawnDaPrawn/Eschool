import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:eschool_people/src/my_school/my_school_cubit.dart';
import 'package:eschool_people/src/my_school/my_school_repository.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

/// Opens a link outside the app: a phone number, an email address, a web
/// page or a document. Tests pass their own.
typedef LinkOpener = Future<bool> Function(Uri uri);

Future<bool> _openOutside(Uri uri) async {
  try {
    return await launchUrl(uri, mode: LaunchMode.externalApplication);
  } catch (_) {
    return false;
  }
}

/// My school: the school's crest and name, how to reach it (a tap calls or
/// writes), its documents and its term dates. A parent with children at
/// two schools picks the school at the top.
class MySchoolScreen extends StatelessWidget {
  const MySchoolScreen({required this.schools, this.openLink, super.key});

  /// The schools to choose from; the first is shown first.
  final List<SchoolSummary> schools;
  final LinkOpener? openLink;

  static Widget withRepository(
    MySchoolRepository repository, {
    required List<SchoolSummary> schools,
    int? initialSchoolId,
    LinkOpener? openLink,
  }) {
    final int first =
        initialSchoolId ?? (schools.isEmpty ? 0 : schools.first.id);
    return BlocProvider<MySchoolCubit>(
      create: (_) => MySchoolCubit(repository, schoolId: first)..load(),
      child: MySchoolScreen(schools: schools, openLink: openLink),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      title: SharedUiUtils.getTranslatedLabel(context, mySchoolKey),
      body: BlocBuilder<MySchoolCubit, MySchoolState>(
        builder: (context, state) {
          final Widget content = switch (state.status) {
            MySchoolStatus.loading => const Center(
              child: CircularProgressIndicator(),
            ),
            MySchoolStatus.unavailable => const NotAvailableYet(),
            MySchoolStatus.failure => LoadFailure(
              error: state.error!,
              onRetry: () => context.read<MySchoolCubit>().load(),
            ),
            MySchoolStatus.loaded => _SchoolDetails(
              info: state.info!,
              fallbackSchool: _summaryOf(state.schoolId),
              openLink: openLink ?? _openOutside,
            ),
          };
          if (schools.length < 2) {
            return content;
          }
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (final SchoolSummary school in schools)
                      ChoiceChip(
                        avatar: SchoolCrest(school: school, radius: 12),
                        label: Text(school.shortName ?? school.name),
                        tooltip: school.name,
                        selected: school.id == state.schoolId,
                        onSelected: (_) =>
                            context.read<MySchoolCubit>().show(school.id),
                      ),
                  ],
                ),
              ),
              Expanded(child: content),
            ],
          );
        },
      ),
    );
  }

  SchoolSummary? _summaryOf(int id) {
    for (final SchoolSummary school in schools) {
      if (school.id == id) {
        return school;
      }
    }
    return null;
  }
}

class _SchoolDetails extends StatelessWidget {
  const _SchoolDetails({
    required this.info,
    required this.fallbackSchool,
    required this.openLink,
  });

  final MySchoolInfo info;
  final SchoolSummary? fallbackSchool;
  final LinkOpener openLink;

  Future<void> _open(BuildContext context, Uri uri) async {
    final supported = ['http', 'https', 'tel', 'mailto'].contains(uri.scheme);
    if ((!supported || !await openLink(uri)) && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            SharedUiUtils.getTranslatedLabel(context, couldNotOpenLinkKey),
          ),
        ),
      );
    }
  }

  Future<void> _openWebLink(BuildContext context, String value) async {
    final uri = Uri.tryParse(value);
    // A malformed or unsafe link gets the same recoverable message.
    return _open(
      context,
      uri != null &&
              ['http', 'https'].contains(uri.scheme) &&
              uri.host.isNotEmpty
          ? uri
          : Uri(scheme: 'unsupported'),
    );
  }

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final TextTheme text = Theme.of(context).textTheme;
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final SchoolSummary? school = info.school ?? fallbackSchool;
    final bool hasContacts =
        info.phone != null ||
        info.email != null ||
        info.website != null ||
        info.address != null;
    final bool empty =
        !hasContacts && info.documents.isEmpty && info.termDates.isEmpty;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
      children: [
        Center(child: SchoolCrest(school: school, radius: 40)),
        const SizedBox(height: 12),
        if (school != null)
          Semantics(
            header: true,
            child: Text(
              school.name,
              textAlign: TextAlign.center,
              style: text.titleLarge?.copyWith(color: colors.onSurface),
            ),
          ),
        if (empty) ...[
          const SizedBox(height: 24),
          Text(
            label(schoolDetailsNotAddedYetKey),
            textAlign: TextAlign.center,
            style: text.bodyMedium?.copyWith(color: colors.onSurfaceVariant),
          ),
        ],
        if (hasContacts) ...[
          const SizedBox(height: 16),
          SectionHeading(label(schoolContactsKey)),
          Card.outlined(
            child: Column(
              children: [
                if (info.phone != null)
                  _LinkTile(
                    icon: Icons.phone,
                    title: info.phone!,
                    action: label(callSchoolKey),
                    onTap: () => _open(
                      context,
                      Uri(scheme: 'tel', path: info.phone!.replaceAll(' ', '')),
                    ),
                  ),
                if (info.email != null)
                  _LinkTile(
                    icon: Icons.email_outlined,
                    title: info.email!,
                    action: label(emailSchoolKey),
                    onTap: () =>
                        _open(context, Uri(scheme: 'mailto', path: info.email)),
                  ),
                if (info.website != null)
                  _LinkTile(
                    icon: Icons.language,
                    title: info.website!,
                    action: label(schoolWebsiteKey),
                    onTap: () => _openWebLink(context, info.website!),
                  ),
                if (info.address != null)
                  ListTile(
                    leading: const Icon(Icons.place_outlined),
                    title: Text(info.address!),
                    subtitle: Text(label(schoolAddressKey)),
                  ),
              ],
            ),
          ),
        ],
        if (info.documents.isNotEmpty) ...[
          const SizedBox(height: 16),
          SectionHeading(label(schoolDocumentsKey)),
          Card.outlined(
            child: Column(
              children: [
                for (final SchoolDocument document in info.documents)
                  _LinkTile(
                    icon: Icons.description_outlined,
                    title: document.title,
                    onTap: () => _openWebLink(context, document.url),
                  ),
              ],
            ),
          ),
        ],
        if (info.termDates.isNotEmpty) ...[
          const SizedBox(height: 16),
          SectionHeading(label(termDatesKey)),
          Card.outlined(
            child: Column(
              children: [
                for (final TermDates term in info.termDates)
                  ListTile(
                    leading: const Icon(Icons.event_outlined),
                    title: Text(
                      term.gradeFrom != null &&
                              term.gradeTo != null &&
                              (term.gradeFrom != 0 || term.gradeTo != 12)
                          ? '${term.name} · ${label(gradeKey)} ${term.gradeFrom == 0 ? 'R' : term.gradeFrom}–${term.gradeTo}'
                          : term.name,
                    ),
                    subtitle: Text(
                      [
                        if (term.startsOn != null) formatDay(term.startsOn!),
                        if (term.endsOn != null) formatDay(term.endsOn!),
                      ].join(' – '),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}

/// A row that opens something: what it is, and what a tap does.
class _LinkTile extends StatelessWidget {
  const _LinkTile({
    required this.icon,
    required this.title,
    required this.onTap,
    this.action,
  });

  final IconData icon;
  final String title;
  final String? action;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: [?action, title].join(', '),
      excludeSemantics: true,
      onTap: onTap,
      child: ListTile(
        leading: Icon(icon),
        title: Text(title),
        subtitle: action == null ? null : Text(action!),
        trailing: const Icon(Icons.open_in_new, size: 18),
        onTap: onTap,
      ),
    );
  }
}
