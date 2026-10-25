import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'weekly_digest_repository.dart';

class WeeklyDigestScreen extends StatefulWidget {
  const WeeklyDigestScreen({
    required this.repository,
    required this.learnerId,
    required this.onHomeAction,
    this.now,
    super.key,
  });
  final WeeklyDigestRepository repository;
  final int learnerId;
  final DateTime Function()? now;
  final void Function(WeeklyDigestHomeAction action) onHomeAction;
  @override
  State<WeeklyDigestScreen> createState() => _WeeklyDigestScreenState();
}

class _WeeklyDigestScreenState extends State<WeeklyDigestScreen>
    with WidgetsBindingObserver {
  final _scroll = ScrollController();
  WeeklyDigestAccess? _access;
  WeeklyDigestDetail? _detail;
  List<WeeklyDigestListItem> _weeks = [];
  Map<String, dynamic>? _pending;
  Timer? _expiry;
  int _epoch = 0;
  int? _next;
  bool _busy = false, _loading = true, _suspended = false;
  String? _error;
  DateTime get _now => (widget.now ?? DateTime.now)().toUtc();
  String l(String text) => academicText(context, text);
  bool get _active => mounted && !_suspended && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_load());
  }

  void _erase({bool pending = true}) {
    _expiry?.cancel();
    _access = null;
    _detail = null;
    _weeks = [];
    _next = null;
    if (pending) _pending = null;
  }

  @override
  void dispose() {
    _epoch++;
    _erase();
    _scroll.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didUpdateWidget(WeeklyDigestScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.learnerId != widget.learnerId) {
      _epoch++;
      _erase();
      unawaited(_load());
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _suspended = true;
        _busy = false;
        _erase(pending: false);
      });
    } else {
      setState(() => _suspended = false);
      unawaited(_load(preservePending: true));
    }
  }

  void _top() => WidgetsBinding.instance.addPostFrameCallback((_) {
    if (mounted && _scroll.hasClients) _scroll.jumpTo(0);
  });
  void _scheduleExpiry(int epoch) {
    _expiry?.cancel();
    final detail = _detail;
    if (!_current(epoch) || detail == null) return;
    final remaining = detail.expiresAt.difference(_now);
    if (remaining <= Duration.zero) {
      setState(() {
        _erase();
        _error = 'This weekly update is no longer available.';
      });
      return;
    }
    _expiry = Timer(
      remaining > const Duration(hours: 12)
          ? const Duration(hours: 12)
          : remaining,
      () => _scheduleExpiry(epoch),
    );
  }

  Future<void> _load({bool preservePending = false}) async {
    final epoch = ++_epoch;
    setState(() {
      _erase(pending: !preservePending);
      _loading = true;
      _error = null;
    });
    try {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      if (!access.children.any((child) => child.id == widget.learnerId))
        throw ApiException(
          'Your weekly update access changed. Reopen this screen.',
          statusCode: 403,
          isApiMessage: true,
        );
      final page = await widget.repository.weeks(widget.learnerId);
      if (!_current(epoch)) return;
      final detail = page.items.isEmpty
          ? null
          : await widget.repository.detail(page.items.first.id);
      if (!_current(epoch)) return;
      if (detail != null && detail.learnerId != widget.learnerId)
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      setState(() {
        _access = access;
        _weeks = page.items;
        _next = page.nextBefore;
        _detail = detail;
        _loading = false;
      });
      _scheduleExpiry(epoch);
    } catch (error) {
      if (!mounted || epoch != _epoch) return;
      // A lost refresh says nothing about a previously dispatched write. Keep
      // its bounded exact retry intent; all visible private data stays erased.
      final uncertain =
          error is! ApiException ||
          error.isTransportFailure ||
          error.statusCode == null ||
          error.statusCode! >= 500;
      setState(() {
        _erase(pending: !uncertain);
        _loading = false;
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'Weekly updates are unavailable. Refresh to check your access.';
      });
      _top();
    }
  }

  Future<void> _open(int id) async {
    if (_busy || _loading || _pending != null) return;
    final epoch = ++_epoch;
    _expiry?.cancel();
    setState(() {
      _detail = null;
      _loading = true;
      _error = null;
    });
    try {
      final detail = await widget.repository.detail(id);
      if (!_current(epoch)) return;
      if (detail.learnerId != widget.learnerId)
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      setState(() {
        _detail = detail;
        _loading = false;
      });
      _scheduleExpiry(epoch);
      _top();
    } catch (_) {
      if (!mounted || epoch != _epoch) return;
      setState(() {
        _erase();
        _loading = false;
        _error =
            'Weekly updates are unavailable. Refresh to check your access.';
      });
      _top();
    }
  }

  Future<void> _more() async {
    if (_busy || _loading || _pending != null || _next == null) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      final page = await widget.repository.weeks(
        widget.learnerId,
        before: _next,
      );
      if (!_current(epoch)) return;
      setState(() {
        _weeks = [..._weeks, ...page.items];
        _next = page.nextBefore;
        _busy = false;
      });
    } catch (_) {
      if (_current(epoch)) {
        setState(() {
          _erase();
          _busy = false;
          _error = 'Refresh weekly updates to check your access.';
        });
        _top();
      }
    }
  }

  Future<void> _language(String value) async {
    if (_busy || _loading || _pending != null || _access == null) return;
    _pending = {
      'operation_id': const Uuid().v4(),
      'revision': _access!.preferenceRevision,
      'language': value,
    };
    await _save();
  }

  Future<void> _save() async {
    final data = _pending;
    if (data == null || !_active || _busy || _loading) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.preference(data);
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
        _pending = null;
      });
      await _load();
    } catch (error) {
      if (!_current(epoch)) return;
      final unknown =
          error is! ApiException ||
          error.isTransportFailure ||
          error.statusCode == null ||
          error.statusCode! >= 500;
      setState(() {
        _busy = false;
        if (!unknown) _erase();
        _error = unknown
            ? 'The language change result is unknown. Retry the same change.'
            : 'Refresh weekly updates before changing language again.';
      });
      _top();
    }
  }

  @override
  Widget build(BuildContext context) {
    final access = _access, detail = _detail;
    return ScreenScaffold(
      title: l('Weekly learning update'),
      body: !_active
          ? Center(
              child: Text(
                l('Your weekly update access changed. Reopen this screen.'),
              ),
            )
          : ListView(
              controller: _scroll,
              padding: const EdgeInsets.all(16),
              children: [
                if (_error != null)
                  Text(
                    l(_error!),
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                if (_pending != null)
                  FilledButton(
                    onPressed: !_loading && !_busy ? _save : null,
                    child: Text(l('Retry same language change')),
                  ),
                if (_pending == null)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: OutlinedButton(
                      onPressed: !_busy ? () => _load() : null,
                      child: Text(l('Refresh')),
                    ),
                  ),
                if (_loading || _busy) const LinearProgressIndicator(),
                if (access != null) ...[
                  DropdownButtonFormField<String>(
                    initialValue: access.language,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: l('Preferred digest language'),
                    ),
                    items: access.languages
                        .map(
                          (code) =>
                              DropdownMenuItem(value: code, child: Text(code)),
                        )
                        .toList(),
                    onChanged: !_loading && !_busy && _pending == null
                        ? (value) {
                            if (value != null) unawaited(_language(value));
                          }
                        : null,
                  ),
                  Text(l('Language changes apply to future weekly updates.')),
                  if (!access.approvedLanguages.contains(access.language))
                    Text(
                      l(
                        'Your school has not approved a weekly template in this language yet. You can choose another approved language.',
                      ),
                    ),
                  if (_weeks.isNotEmpty)
                    DropdownButtonFormField<int>(
                      key: ValueKey('week:${detail?.id}'),
                      initialValue: detail?.id,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: l('Week')),
                      items: _weeks
                          .map(
                            (week) => DropdownMenuItem(
                              value: week.id,
                              child: Text(
                                '${week.weekStart} – ${week.weekEnd}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: !_loading && !_busy && _pending == null
                          ? (id) {
                              if (id != null) unawaited(_open(id));
                            }
                          : null,
                    ),
                  if (detail != null)
                    Directionality(
                      textDirection: detail.language == 'ar'
                          ? TextDirection.rtl
                          : TextDirection.ltr,
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                detail.title,
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                              const SizedBox(height: 12),
                              Text(detail.summary),
                              const SizedBox(height: 16),
                              FilledButton(
                                onPressed:
                                    !_loading &&
                                        !_busy &&
                                        _pending == null &&
                                        detail.homeAction.kind !=
                                            WeeklyDigestHomeActionKindEnum
                                                .unknownDefaultOpenApi
                                    ? () {
                                        if (_active &&
                                            detail.expiresAt.isAfter(_now))
                                          widget.onHomeAction(
                                            detail.homeAction,
                                          );
                                      }
                                    : null,
                                child: Text(detail.homeAction.label),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  if (!_loading && _weeks.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Text(
                        l(
                          'No weekly updates yet. Your school sends them on Mondays after reviewing your language.',
                        ),
                      ),
                    ),
                  if (_next != null)
                    OutlinedButton(
                      onPressed: !_loading && !_busy && _pending == null
                          ? _more
                          : null,
                      child: Text(l('Older weeks')),
                    ),
                ],
              ],
            ),
    );
  }
}
