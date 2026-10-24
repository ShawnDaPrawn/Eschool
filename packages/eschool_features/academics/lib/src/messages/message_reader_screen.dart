import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../content/content_widgets.dart';
import 'message_repository.dart';
import 'message_cubit.dart';

class SchoolMessageReaderScreen extends StatefulWidget {
  const SchoolMessageReaderScreen({
    required this.repository,
    required this.id,
    this.embedded = false,
    this.refreshEpoch = 0,
    this.onRead,
    super.key,
  });
  final SchoolMessageRepository repository;
  final int id;
  final bool embedded;
  final int refreshEpoch;
  final ValueChanged<int>? onRead;
  @override
  State<SchoolMessageReaderScreen> createState() =>
      _SchoolMessageReaderScreenState();
}

class _SchoolMessageReaderScreenState extends State<SchoolMessageReaderScreen>
    with WidgetsBindingObserver {
  late MessageReaderCubit _cubit;
  void _create() {
    _cubit = MessageReaderCubit(widget.repository, widget.id)..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant SchoolMessageReaderScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository || old.id != widget.id) {
      _cubit.close();
      _create();
    } else if (old.refreshEpoch != widget.refreshEpoch)
      _cubit.load();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _cubit.load();
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state)) {
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
  Widget build(BuildContext context) {
    String l(String text) => contentLabel(context, text);
    final body = BlocConsumer<MessageReaderCubit, MessageReaderState>(
      bloc: _cubit,
      listenWhen: (old, next) => !old.read && next.read,
      listener: (_, _) => widget.onRead?.call(widget.id),
      builder: (context, state) {
        if (state.busy) return const Center(child: CircularProgressIndicator());
        final reader = state.reader;
        if (reader == null) {
          return Center(
            child: ContentMessage(
              state.error ?? 'Connect to read this private school message.',
              retry: _cubit.load,
            ),
          );
        }
        final published = reader.publishedAt?.toLocal();
        return RefreshIndicator(
          onRefresh: _cubit.load,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Semantics(
                  header: true,
                  child: Text(
                    reader.title,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                ),
                if (published != null)
                  Text(
                    '${l('Published')}: ${MaterialLocalizations.of(context).formatFullDate(published)}',
                  ),
                const SizedBox(height: 12),
                ContentMarkdownView(markdown: reader.markdown),
                const SizedBox(height: 20),
                Text(
                  l(
                    state.read
                        ? 'Read acknowledgement saved'
                        : 'Read acknowledgement pending',
                  ),
                ),
                if (state.acknowledgementError != null) ...[
                  Text(l(state.acknowledgementError!)),
                  TextButton(
                    onPressed: _cubit.acknowledge,
                    child: Text(l('Retry read acknowledgement')),
                  ),
                ],
                TextButton.icon(
                  onPressed: _cubit.load,
                  icon: const Icon(Icons.refresh),
                  label: Text(l('Refresh message')),
                ),
              ],
            ),
          ),
        );
      },
    );
    return widget.embedded
        ? body
        : ScreenScaffold(
            title: l('School messages'),
            body: ContentColumn(maxWidth: 1000, child: body),
          );
  }
}
