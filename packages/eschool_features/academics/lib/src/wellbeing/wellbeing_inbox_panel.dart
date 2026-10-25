import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import '../localization/academic_text.dart';
import 'wellbeing_notice_repository.dart';

/// Embedded in its owning private reader, so context replacement disposes it.
class WellbeingInboxPanel extends StatefulWidget {
  const WellbeingInboxPanel({
    required this.repository,
    required this.onReturn,
    required this.onDenied,
    super.key,
  });
  final WellbeingNoticeRepository repository;
  final Future<void> Function() onReturn;
  final VoidCallback onDenied;
  @override
  State<WellbeingInboxPanel> createState() => _WellbeingInboxPanelState();
}

class _WellbeingInboxPanelState extends State<WellbeingInboxPanel>
    with WidgetsBindingObserver {
  WellbeingNoticePage? _page;
  bool _busy = true, _visible = true;
  int _epoch = 0;
  String? _error;
  String l(String text) => academicText(context, text);
  bool _current(int epoch) =>
      mounted && _visible && epoch == _epoch && widget.repository.isActive;
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
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted)
        setState(() {
          _visible = false;
          _page = null;
        });
    }
  }

  Future<void> _run(Future<void> Function(int) action) async {
    if (!widget.repository.isActive || !_visible) return;
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
        widget.onDenied();
        return;
      }
      _error = l('Private updates could not be loaded. Try again.');
    } finally {
      if (_current(epoch)) setState(() => _busy = false);
    }
  }

  Future<void> _load(int page) => _run((epoch) async {
    final result = await widget.repository.notices(page);
    if (_current(epoch)) _page = result;
  });
  Future<void> _open(int id) => _run((epoch) async {
    final result = await widget.repository.readNotice(id);
    if (!_current(epoch)) return;
    if (result.id != id)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    await widget.onReturn();
  });
  @override
  Widget build(BuildContext context) {
    if (!_visible || !widget.repository.isActive)
      return const SizedBox.shrink();
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(
          l('Private updates'),
          style: Theme.of(context).textTheme.titleLarge,
        ),
        Text(
          l(
            'Updates contain no report or case details. Open your private area to review the current record.',
          ),
        ),
        if (_busy) const LinearProgressIndicator(),
        if (_error != null) Semantics(liveRegion: true, child: Text(_error!)),
        Wrap(
          spacing: 12,
          children: [
            TextButton(
              onPressed: _busy ? null : () => _load(_page?.page ?? 1),
              child: Text(l('Refresh private updates')),
            ),
            TextButton(
              onPressed: _busy ? null : widget.onReturn,
              child: Text(l('Return to private area')),
            ),
          ],
        ),
        if (_page != null) ...[
          Text('${l('Unread private updates')}: ${_page!.unreadCount}'),
          if (_page!.items.isEmpty)
            Text(l('No private updates are available.')),
          for (final item in _page!.items)
            ListTile(
              key: ValueKey('wellbeing-notice-${item.id}'),
              leading: Icon(
                item.readAt == null
                    ? Icons.mark_email_unread_outlined
                    : Icons.drafts_outlined,
              ),
              title: Text(l('Private school update')),
              subtitle: Text(
                l(
                  'Open your private school area to review an available update.',
                ),
              ),
              onTap: _busy ? null : () => _open(item.id),
            ),
          Row(
            children: [
              IconButton(
                tooltip: l('Previous page'),
                onPressed: !_busy && _page!.page > 1
                    ? () => _load(_page!.page - 1)
                    : null,
                icon: const Icon(Icons.chevron_left),
              ),
              Text('${_page!.page} / ${_page!.lastPage}'),
              IconButton(
                tooltip: l('Next page'),
                onPressed: !_busy && _page!.page < _page!.lastPage
                    ? () => _load(_page!.page + 1)
                    : null,
                icon: const Icon(Icons.chevron_right),
              ),
            ],
          ),
        ],
      ],
    );
  }
}
