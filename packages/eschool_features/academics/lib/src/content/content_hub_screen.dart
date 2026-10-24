import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'content_repository.dart';
import 'content_cubit.dart';
import 'content_editor_screen.dart';
import 'content_widgets.dart';

class ContentHubScreen extends StatefulWidget {
  const ContentHubScreen({
    required this.repository,
    this.pickFile = pickLearningContent,
    super.key,
  });
  final AcademicContentRepository repository;
  final ContentFilePicker pickFile;
  @override
  State<ContentHubScreen> createState() => _ContentHubScreenState();
}

class _ContentHubScreenState extends State<ContentHubScreen>
    with WidgetsBindingObserver {
  ContentConfiguration? _configuration;
  List<ContentListItem> _items = [];
  bool _busy = false;
  String? _error;
  int _generation = 0, _page = 0, _lastPage = 1;
  String label(String text) => contentLabel(context, text);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _generation++;
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _load(year: _configuration?.sessionYearId);
    } else if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden ||
        state == AppLifecycleState.paused) {
      _generation++;
      if (mounted) {
        setState(() {
          _configuration = null;
          _items = [];
        });
      }
    }
  }

  Future<void> _load({int? year}) async {
    final generation = ++_generation;
    setState(() {
      _busy = true;
      _error = null;
      _items = [];
      _configuration = null;
    });
    try {
      final config = await widget.repository.configuration(year: year),
          items = await widget.repository.items(config.sessionYearId);
      if (!mounted || generation != _generation) return;
      if (!widget.repository.isActive) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      setState(() {
        _configuration = config;
        _items = items.items;
        _page = items.page;
        _lastPage = items.lastPage;
      });
    } catch (error) {
      if (mounted && generation == _generation) {
        setState(() => _error = contentFailure(error));
      }
    } finally {
      if (mounted && generation == _generation) setState(() => _busy = false);
    }
  }

  Future<void> _more() async {
    final config = _configuration;
    if (_busy || config == null || _page >= _lastPage) return;
    final generation = _generation;
    setState(() => _busy = true);
    try {
      final data = await widget.repository.items(
        config.sessionYearId,
        page: _page + 1,
      );
      if (!mounted ||
          generation != _generation ||
          !widget.repository.isActive) {
        return;
      }
      setState(() {
        _items = {
          for (final i in [..._items, ...data.items]) i.id: i,
        }.values.toList();
        _page = data.page;
        _lastPage = data.lastPage;
      });
    } catch (error) {
      if (mounted && generation == _generation) {
        setState(() {
          _items = [];
          _error = contentFailure(error);
        });
      }
    } finally {
      if (mounted && generation == _generation) setState(() => _busy = false);
    }
  }

  Future<void> _open([int? id]) async {
    final config = _configuration;
    if (config == null) return;
    await Navigator.of(context).push(
      AdaptivePageRoute(
        builder: (_) => ContentEditorScreen(
          repository: widget.repository,
          configuration: config,
          itemId: id,
          pickFile: widget.pickFile,
        ),
      ),
    );
    if (mounted) _load(year: config.sessionYearId);
  }

  Future<void> _controls() async {
    final settings = _configuration!.settings;
    final controller = TextEditingController(
      text: settings.dailyTranscriptionSeconds.toString(),
    );
    var enabled = settings.transcriptionEnabled;
    final selected = await showDialog<ContentSettings>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialog) => AlertDialog(
          title: Text(label('School transcription controls')),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(label('Audio and video transcription')),
                value: enabled,
                onChanged: (v) => setDialog(() => enabled = v),
              ),
              TextField(
                controller: controller,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: label('Daily allowance in seconds'),
                  helperText: label('Choose between 600 and 28800 seconds.'),
                ),
                onChanged: (_) => setDialog(() => {}),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(label('Cancel')),
            ),
            FilledButton(
              onPressed:
                  (int.tryParse(controller.text) ?? 0) < 600 ||
                      (int.tryParse(controller.text) ?? 0) > 28800
                  ? null
                  : () => Navigator.pop(
                      context,
                      ContentSettings(
                        transcriptionEnabled: enabled,
                        dailyTranscriptionSeconds: int.parse(controller.text),
                        revision: settings.revision,
                      ),
                    ),
              child: Text(label('Save controls')),
            ),
          ],
        ),
      ),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) => controller.dispose());
    if (selected == null) return;
    setState(() => _busy = true);
    try {
      await widget.repository.settings(selected);
      if (mounted) await _load(year: _configuration?.sessionYearId);
    } catch (error) {
      if (mounted) {
        setState(() {
          _error = contentFailure(error);
          _busy = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: label('Learning content'),
    body: ContentColumn(
      maxWidth: 1100,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_busy) const LinearProgressIndicator(),
          if (_error != null) ContentMessage(_error!, retry: () => _load()),
          if (_configuration != null)
            Padding(
              padding: const EdgeInsets.all(16),
              child: Wrap(
                spacing: 12,
                runSpacing: 12,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  SizedBox(
                    width: 220,
                    child: DropdownButtonFormField<int>(
                      initialValue: _configuration!.sessionYearId,
                      decoration: InputDecoration(
                        labelText: label('School year'),
                        border: const OutlineInputBorder(),
                      ),
                      items: [
                        for (final y in _configuration!.schoolYears)
                          DropdownMenuItem(value: y.id, child: Text(y.name)),
                      ],
                      onChanged: _busy ? null : (id) => _load(year: id),
                    ),
                  ),
                  FilledButton.icon(
                    onPressed: _busy || _configuration!.groups.isEmpty
                        ? null
                        : () => _open(),
                    icon: const Icon(Icons.upload_file),
                    label: Text(label('New content upload')),
                  ),
                  if (_configuration!.canManage)
                    OutlinedButton(
                      onPressed: _busy ? null : _controls,
                      child: Text(label('School transcription controls')),
                    ),
                  IconButton(
                    tooltip: label('Refresh'),
                    onPressed: _busy
                        ? null
                        : () => _load(year: _configuration!.sessionYearId),
                    icon: const Icon(Icons.refresh),
                  ),
                ],
              ),
            ),
          if (_configuration != null && _configuration!.groups.isEmpty)
            ContentMessage(
              'No current teaching groups are available. Ask the school to check your allocations.',
            ),
          if (!_busy && _error == null && _items.isEmpty)
            ContentMessage(
              'No learning material yet. Upload a private source to begin.',
            ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => _load(year: _configuration?.sessionYearId),
              child: ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                itemCount: _items.length + (_page < _lastPage ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index == _items.length) {
                    return TextButton(
                      onPressed: _busy ? null : _more,
                      child: Text(label('Load more')),
                    );
                  }
                  final item = _items[index];
                  return Card(
                    margin: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 6,
                    ),
                    child: ListTile(
                      leading: const Icon(Icons.menu_book_outlined),
                      title: Text(item.title),
                      subtitle: Text(
                        '${label(contentState(item.draftState?.value ?? 'published'))} · ${label(item.active ? 'Active' : 'Withdrawn')}',
                      ),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () => _open(item.id),
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    ),
  );
}
