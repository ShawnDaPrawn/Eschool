import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import 'private_staff_notice_repository.dart';

/// Only generic notice metadata is retained; owning profile epoch removes this route.
class PrivateStaffInboxScreen extends StatefulWidget {
  const PrivateStaffInboxScreen({
    required this.repository,
    required this.openFeature,
    required this.canOpen,
    super.key,
  });
  final PrivateStaffNoticeRepository repository;
  final Future<void> Function(String feature) openFeature;
  final bool Function(String feature) canOpen;
  @override
  State<PrivateStaffInboxScreen> createState() =>
      _PrivateStaffInboxScreenState();
}

class _PrivateStaffInboxScreenState extends State<PrivateStaffInboxScreen>
    with WidgetsBindingObserver {
  StaffPrivateNoticePage? _page;
  int _epoch = 0;
  bool _visible = true, _busy = false, _denied = false;
  int? _pendingId;
  String? _pendingFeature, _error;
  String l(String s) => academicText(context, s);
  bool _current(int epoch) =>
      mounted &&
      _visible &&
      !_denied &&
      epoch == _epoch &&
      widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load(1);
  }

  @override
  void dispose() {
    _epoch++;
    _page = null;
    _pendingId = null;
    _pendingFeature = null;
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted) {
        setState(() {
          _visible = false;
          _page = null;
          _pendingId = null;
          _pendingFeature = null;
        });
      }
    }
    // Resume requires a fresh owning staff profile; a suspended reader never reactivates itself.
  }

  Future<void> _run(Future<void> Function(int) action) async {
    if (!_visible || _denied || !widget.repository.isActive) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await action(epoch);
    } catch (error) {
      if (!_current(epoch)) return;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _page = null;
        _pendingId = null;
        _pendingFeature = null;
        _denied = true;
        _error = l(
          'Private updates are no longer available. Refresh staff access.',
        );
      } else {
        _error = l(
          _pendingId == null
              ? 'Private updates could not be loaded. Try again.'
              : 'The read confirmation is unknown. Retry the same update.',
        );
      }
    } finally {
      if (mounted && _visible && epoch == _epoch) setState(() => _busy = false);
    }
  }

  Future<void> _load(int page) => _run((epoch) async {
    final result = await widget.repository.notices(page);
    if (_current(epoch)) _page = result;
  });
  Future<void> _open(StaffPrivateNotice item) async {
    if (!widget.canOpen(item.feature)) return;
    if (item.writable && _page?.writable == true && item.readAt == null) {
      _pendingId = item.id;
      _pendingFeature = item.feature;
      await _confirm();
    } else {
      await _run((_) => _navigate(item.feature));
    }
  }

  Future<void> _confirm() => _run((epoch) async {
    final id = _pendingId, feature = _pendingFeature;
    if (id == null || feature == null) return;
    await widget.repository.markRead(id);
    if (!_current(epoch)) return;
    _pendingId = null;
    _pendingFeature = null;
    // A confirmed marker stays confirmed if opening or refreshing the feature fails.
    await _navigate(feature);
  });
  Future<void> _navigate(String feature) async {
    if (!_visible || !widget.repository.isActive || !widget.canOpen(feature)) {
      return;
    }
    final page = _page?.page ?? 1;
    setState(() => _page = null);
    await widget.openFeature(feature);
    if (mounted && _visible && widget.repository.isActive && !_denied) {
      await _load(page);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_visible || !widget.repository.isActive) {
      return const SizedBox.shrink();
    }
    return Scaffold(
      appBar: AppBar(title: Text(l('Private staff updates'))),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            l(
              'Updates contain no private record details. Each school area checks your current access when opened.',
            ),
          ),
          if (_busy) const LinearProgressIndicator(),
          if (_error != null) Semantics(liveRegion: true, child: Text(_error!)),
          if (_pendingId != null)
            FilledButton(
              onPressed: _busy ? null : _confirm,
              child: Text(l('Retry the same read confirmation')),
            ),
          if (!_denied && _pendingId == null)
            TextButton(
              onPressed: _busy ? null : () => _load(_page?.page ?? 1),
              child: Text(l('Refresh private updates')),
            ),
          if (_page != null) ...[
            Text('${l('Unread private updates')}: ${_page!.unreadCount}'),
            if (_page!.items.isEmpty)
              Text(l('No private updates are available.')),
            for (final item in _page!.items)
              ListTile(
                key: ValueKey('private-staff-notice-${item.id}'),
                leading: Icon(
                  item.readAt == null
                      ? Icons.mark_email_unread_outlined
                      : Icons.drafts_outlined,
                ),
                title: Text(l(item.title)),
                subtitle: Text(l(item.message)),
                onTap:
                    _busy || _pendingId != null || !widget.canOpen(item.feature)
                    ? null
                    : () => _open(item),
              ),
            Row(
              children: [
                IconButton(
                  tooltip: l('Previous page'),
                  onPressed: !_busy && _pendingId == null && _page!.page > 1
                      ? () => _load(_page!.page - 1)
                      : null,
                  icon: const Icon(Icons.chevron_left),
                ),
                Text('${_page!.page} / ${_page!.lastPage}'),
                IconButton(
                  tooltip: l('Next page'),
                  onPressed:
                      !_busy &&
                          _pendingId == null &&
                          _page!.page < _page!.lastPage
                      ? () => _load(_page!.page + 1)
                      : null,
                  icon: const Icon(Icons.chevron_right),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
