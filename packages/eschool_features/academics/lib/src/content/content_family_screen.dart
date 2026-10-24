import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'content_repository.dart';
import 'content_cubit.dart';
import 'content_widgets.dart';

class LearningContentScreen extends StatefulWidget {
  const LearningContentScreen({
    required this.repository,
    required this.learner,
    required this.learnerName,
    this.initialVersion,
    super.key,
  });
  final AcademicContentRepository repository;
  final int learner;
  final String learnerName;
  final int? initialVersion;
  @override
  State<LearningContentScreen> createState() => _LearningContentScreenState();
}

class _LearningContentScreenState extends State<LearningContentScreen>
    with WidgetsBindingObserver {
  late ContentLibraryCubit _cubit;
  void _create() {
    _cubit = ContentLibraryCubit(widget.repository, widget.learner)..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
    if (widget.initialVersion != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _open(
            widget.initialVersion!,
            contentLabel(context, 'Learning content'),
          );
        }
      });
    }
  }

  @override
  void didUpdateWidget(covariant LearningContentScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository || old.learner != widget.learner) {
      _cubit.close();
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _cubit.load();
    } else if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.paused ||
        state == AppLifecycleState.hidden) {
      _cubit.clear();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  Future<void> _open(int id, String title) async {
    await Navigator.of(context).push(
      AdaptivePageRoute(
        builder: (_) => LearningContentReaderScreen(
          repository: widget.repository,
          learner: widget.learner,
          version: id,
          title: title,
        ),
      ),
    );
    if (mounted) _cubit.load();
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: contentLabel(context, 'Learning content'),
    body: ContentColumn(
      maxWidth: 1100,
      child: BlocBuilder<ContentLibraryCubit, ContentLibraryState>(
        bloc: _cubit,
        builder: (context, state) => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      widget.learnerName,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  IconButton(
                    tooltip: contentLabel(context, 'Refresh'),
                    onPressed: state.busy ? null : () => _cubit.load(),
                    icon: const Icon(Icons.refresh),
                  ),
                ],
              ),
            ),
            if (state.busy) const LinearProgressIndicator(),
            if (state.error != null)
              ContentMessage(state.error!, retry: () => _cubit.load()),
            if (!state.busy && state.error == null && state.items.isEmpty)
              ContentMessage(
                'No learning material is published for your current groups yet.',
              ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => _cubit.load(),
                child: ListView.builder(
                  physics: const AlwaysScrollableScrollPhysics(),
                  itemCount:
                      state.items.length +
                      (state.page < state.lastPage ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index == state.items.length) {
                      return TextButton(
                        onPressed: state.busy
                            ? null
                            : () => _cubit.load(more: true),
                        child: Text(contentLabel(context, 'Load more')),
                      );
                    }
                    final row = state.items[index];
                    return Card(
                      margin: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 6,
                      ),
                      child: ListTile(
                        leading: const Icon(Icons.menu_book_outlined),
                        title: Text(row.title),
                        subtitle: Text('${row.subjectName}\n${row.summary}'),
                        isThreeLine: row.summary.isNotEmpty,
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => _open(row.versionId, row.title),
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class LearningContentReaderScreen extends StatefulWidget {
  const LearningContentReaderScreen({
    required this.repository,
    required this.learner,
    required this.version,
    required this.title,
    super.key,
  });
  final AcademicContentRepository repository;
  final int learner, version;
  final String title;
  @override
  State<LearningContentReaderScreen> createState() =>
      _LearningContentReaderScreenState();
}

class _LearningContentReaderScreenState
    extends State<LearningContentReaderScreen>
    with WidgetsBindingObserver {
  late ContentReaderCubit _cubit;
  void _create() {
    _cubit = ContentReaderCubit(
      widget.repository,
      learner: widget.learner,
      version: widget.version,
    )..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant LearningContentReaderScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository ||
        old.learner != widget.learner ||
        old.version != widget.version) {
      _cubit.close();
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _cubit.load();
    } else if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden ||
        state == AppLifecycleState.paused) {
      _cubit.clear();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: contentLabel(context, 'Learning content'),
    body: ContentColumn(
      maxWidth: 960,
      child: BlocBuilder<ContentReaderCubit, ContentReaderState>(
        bloc: _cubit,
        builder: (context, state) {
          if (state.busy) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state.reader == null) {
            return Center(
              child: ContentMessage(
                state.error ??
                    'Connect to open this private learning material.',
                retry: () => _cubit.load(),
              ),
            );
          }
          final reader = state.reader!;
          return RefreshIndicator(
            onRefresh: () => _cubit.load(),
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    reader.title,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  if (reader.summary.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(reader.summary),
                    ),
                  if (reader.topics.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          for (final topic in reader.topics)
                            Chip(label: Text(topic.name)),
                        ],
                      ),
                    ),
                  ContentMarkdownView(markdown: reader.markdown),
                ],
              ),
            ),
          );
        },
      ),
    ),
  );
}
