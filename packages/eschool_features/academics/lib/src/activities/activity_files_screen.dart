import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../content/content_widgets.dart';
import 'activity_file_repository.dart';

/// Remains inside the activity reader's context boundary; never a detached route.
class ActivityFilesScreen extends StatefulWidget {
  const ActivityFilesScreen({
    required this.repository,
    required this.onChoose,
    this.enabled = true,
    super.key,
  });
  final ActivityFileRepository repository;
  final VoidCallback onChoose;
  final bool enabled;
  @override
  State<ActivityFilesScreen> createState() => _ActivityFilesScreenState();
}

class _ActivityFilesScreenState extends State<ActivityFilesScreen>
    with WidgetsBindingObserver {
  ActivityMaterialAttachmentPage? _page;
  ActivityMaterialAttachment? _open, _confirm;
  (String, ActivityMaterialAttachmentRemove)? _pending;
  bool _foreground = true, _loading = false, _busy = false;
  String? _error;
  int _epoch = 0;
  String l(String text) => contentLabel(context, text);
  bool _active(int epoch) =>
      mounted &&
      _foreground &&
      widget.enabled &&
      widget.repository.isActive &&
      _epoch == epoch;
  void _erase() {
    _page = null;
    _open = null;
    _confirm = null;
    _pending = null;
    _error = null;
    _busy = false;
    _loading = false;
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(ActivityFilesScreen old) {
    super.didUpdateWidget(old);
    if (widget.repository != old.repository || widget.enabled != old.enabled) {
      _epoch++;
      _erase();
      if (widget.enabled) _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _epoch++;
    setState(() {
      _foreground = state == AppLifecycleState.resumed;
      _erase();
    });
    if (_foreground && widget.enabled) _load();
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    _erase();
    super.dispose();
  }

  String _message(Object error) => error is ApiException && error.isApiMessage
      ? error.errorMessage
      : 'The documents could not be loaded. Try again.';
  Future<void> _load() async {
    final epoch = ++_epoch;
    setState(() {
      _erase();
      _loading = true;
    });
    try {
      final page = await widget.repository.listing();
      if (_active(epoch)) {
        setState(() {
          _page = page;
          _loading = false;
        });
      }
    } catch (error) {
      if (mounted && _foreground && epoch == _epoch) {
        setState(() {
          _erase();
          _error = _message(error);
        });
      }
    }
  }

  Future<void> _remove(ActivityMaterialAttachment file) async {
    if (_busy || _page?.canRemove != true || !widget.repository.isActive) {
      return;
    }
    _confirm = null;
    _pending = (
      file.id,
      ActivityMaterialAttachmentRemove(
        operationId: const Uuid().v4(),
        revision: file.revision,
        materialRevision: _page!.materialRevision,
      ),
    );
    await _sendRemoval();
  }

  Future<void> _sendRemoval() async {
    final intent = _pending;
    if (intent == null || _busy) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
      _open = null;
    });
    try {
      await widget.repository.remove(intent.$1, intent.$2);
      if (!_active(epoch)) return;
      _pending = null;
      await _load();
    } catch (error) {
      if (!mounted || !_foreground || epoch != _epoch) return;
      setState(() {
        _busy = false;
        if (!widget.repository.isActive ||
            error is ApiException &&
                [401, 403, 404].contains(error.statusCode)) {
          _erase();
        } else if (error is! ApiException || !error.isTransportFailure) {
          _pending = null;
        }
        _error = _message(error);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_foreground || !widget.enabled || !widget.repository.isActive) {
      return const SizedBox.shrink();
    }
    final file = _open, epoch = _epoch;
    if (file != null) {
      return PrivateDocumentView(
        identity: (widget.repository, file.id, file.revision, epoch),
        name: file.name,
        mime: file.mime.value,
        loadBytes: () => widget.repository.read(file),
        isActive: () => _active(epoch),
        embedded: true,
        onClose: () => setState(() => _open = null),
      );
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(l('Documents'), style: Theme.of(context).textTheme.titleLarge),
        if (_loading || _busy) const LinearProgressIndicator(),
        if (_error != null)
          Semantics(liveRegion: true, child: Text(l(_error!))),
        if (_pending != null) ...[
          Text(
            l(
              'The removal may have finished. Retry the same change to confirm it.',
            ),
          ),
          FilledButton(
            onPressed: _busy ? null : _sendRemoval,
            child: Text(l('Retry the same removal')),
          ),
        ] else if (_confirm != null) ...[
          Text(l('Remove document?')),
          Text(_confirm!.name),
          TextButton(
            onPressed: () => setState(() => _confirm = null),
            child: Text(l('Keep document')),
          ),
          FilledButton(
            onPressed: () => _remove(_confirm!),
            child: Text(l('Remove document')),
          ),
        ] else if (_page != null) ...[
          if (_page!.canUpload)
            OutlinedButton.icon(
              onPressed: _busy ? null : widget.onChoose,
              icon: const Icon(Icons.upload_file),
              label: Text(l('Upload document')),
            ),
          if (!_page!.canUpload && _page!.canRemove)
            Text(
              l(
                'New uploads are unavailable. You can still remove a document.',
              ),
            ),
          if (_page!.canUpload)
            Text(l('PDF up to 5 MB; JPEG or PNG up to 4 MB.')),
          if (_page!.items.isEmpty) Text(l('No documents have been attached.')),
          for (final entry in _page!.items)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      entry.name,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    Text('${(entry.bytes / 1024).ceil()} KB'),
                    Wrap(
                      spacing: 8,
                      children: [
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () => setState(() => _open = entry),
                          child: Text(l('View document')),
                        ),
                        if (_page!.canRemove)
                          TextButton(
                            onPressed: _busy
                                ? null
                                : () => setState(() => _confirm = entry),
                            child: Text(l('Remove document')),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
        ],
        if (_pending == null)
          TextButton(
            onPressed: _busy ? null : _load,
            child: Text(l('Refresh documents')),
          ),
      ],
    );
  }
}
