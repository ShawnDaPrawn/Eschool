import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'academic_labels.dart';
import 'mark_capture_screen.dart';
import 'marks_repository.dart';

class MarkGroupsScreen extends StatefulWidget {
  const MarkGroupsScreen({
    required this.repository,
    required this.schoolName,
    super.key,
  });
  final MarksRepository repository;
  final String schoolName;
  @override
  State<MarkGroupsScreen> createState() => _MarkGroupsScreenState();
}

class _MarkGroupsScreenState extends State<MarkGroupsScreen> {
  late Future<List<TeachingGroup>> _groups = widget.repository.groups();
  String l(String key) => academicLabel(context, key);
  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: '${widget.schoolName} · ${l('marks')}',
    body: FutureBuilder<List<TeachingGroup>>(
      future: _groups,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Center(
            child: TextButton(
              onPressed: () =>
                  setState(() => _groups = widget.repository.groups()),
              child: Text(l('retry')),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final groups = snapshot.data!;
        if (groups.isEmpty) return Center(child: Text(l('noGroups')));
        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: groups.length,
          itemBuilder: (context, index) {
            final group = groups[index];
            return Card(
              child: ListTile(
                title: Text(group.name),
                subtitle: group.yearName == null ? null : Text(group.yearName!),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).push(
                  AdaptivePageRoute(
                    builder: (_) => _GroupTerms(
                      repository: widget.repository,
                      group: group,
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    ),
  );
}

class _GroupTerms extends StatefulWidget {
  const _GroupTerms({required this.repository, required this.group});
  final MarksRepository repository;
  final TeachingGroup group;
  @override
  State<_GroupTerms> createState() => _GroupTermsState();
}

class _GroupTermsState extends State<_GroupTerms> {
  late Future<List<SchoolTerm>> _terms = widget.repository.terms(widget.group);
  String l(String key) => academicLabel(context, key);
  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: '${widget.group.name} · ${widget.group.yearName ?? ''}',
    body: FutureBuilder<List<SchoolTerm>>(
      future: _terms,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Center(
            child: TextButton(
              onPressed: () => setState(
                () => _terms = widget.repository.terms(widget.group),
              ),
              child: Text(l('retry')),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.data!.isEmpty) return Center(child: Text(l('noTerms')));
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            for (final term in snapshot.data!)
              Card(
                child: ListTile(
                  title: Text(term.name),
                  subtitle: Text(
                    '${term.startsOn ?? ''} – ${term.endsOn ?? ''}',
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.of(context).push(
                    AdaptivePageRoute(
                      builder: (_) => MarkCaptureScreen.withRepository(
                        widget.repository,
                        group: widget.group.id,
                        term: term.id,
                        title: '${widget.group.name} · ${term.name}',
                      ),
                    ),
                  ),
                ),
              ),
          ],
        );
      },
    ),
  );
}
