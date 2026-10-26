import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import '../wellbeing/school_wall_time.dart';
import 'family_visit_pending.dart';
import 'family_visit_repository.dart';

class FamilyVisitsScreen extends StatefulWidget {
  const FamilyVisitsScreen({
    required this.repository,
    this.staffOnly = false,
    this.initialLearnerId,
    this.now,
    super.key,
  });
  final FamilyVisitRepository repository;
  final bool staffOnly;
  final int? initialLearnerId;
  final DateTime Function()? now;
  @override
  State<FamilyVisitsScreen> createState() => _FamilyVisitsScreenState();
}

class _FamilyVisitsScreenState extends State<FamilyVisitsScreen>
    with WidgetsBindingObserver {
  FamilyVisitConfiguration? _config;
  final _meetings = <FamilyMeetingSlot>[], _passes = <FamilyGatePass>[];
  final _collector = TextEditingController(), _reason = TextEditingController();
  final _scroll = ScrollController();
  GlobalKey<NavigatorState> _navigator = GlobalKey();
  FamilyVisitPending? _pending;
  int? _learner, _nextMeeting, _nextPass;
  int _generation = 0, _tab = 0;
  bool _foreground = true, _loading = false, _busy = false;
  String? _error, _notice, _starts, _ends;
  String l(String key) => academicText(context, key);
  DateTime _now() => (widget.now?.call() ?? DateTime.now()).toUtc();
  bool _active(int generation) =>
      mounted &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  bool _accept(int generation) {
    if (_active(generation)) return true;
    if (mounted &&
        _foreground &&
        generation == _generation &&
        !widget.repository.isActive) {
      setState(() {
        _erasePrivate();
        _pending?.close();
        _pending = null;
        _error = 'Your meeting access changed. Reopen this screen.';
      });
    }
    return false;
  }

  bool get _idle => !_busy && !_loading && _pending == null && _config != null;
  bool get _canRequest =>
      _idle &&
      _config!.writable &&
      _learner != null &&
      _collector.text.trim().isNotEmpty &&
      _reason.text.trim().isNotEmpty &&
      _starts != null &&
      _ends != null;

  @override
  void initState() {
    super.initState();
    _learner = widget.initialLearnerId;
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  void _erasePrivate() {
    _config = null;
    _meetings.clear();
    _passes.clear();
    _nextMeeting = null;
    _nextPass = null;
    _collector.clear();
    _reason.clear();
    _starts = null;
    _ends = null;
    _error = null;
    _notice = null;
    _busy = false;
    _loading = false;
  }

  @override
  void didUpdateWidget(FamilyVisitsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.staffOnly != widget.staffOnly) {
      _generation++;
      _pending?.close();
      _pending = null;
      _erasePrivate();
      _navigator = GlobalKey();
      _learner = widget.initialLearnerId;
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _generation++;
      setState(() {
        _foreground = false;
        _erasePrivate();
        _navigator = GlobalKey();
      });
    } else {
      _foreground = true;
      _load();
    }
  }

  @override
  void dispose() {
    _generation++;
    WidgetsBinding.instance.removeObserver(this);
    _pending?.close();
    _collector.clear();
    _collector.dispose();
    _reason.clear();
    _reason.dispose();
    _scroll.dispose();
    super.dispose();
  }

  String _message(Object error) => error is ApiException && error.isApiMessage
      ? error.errorMessage
      : error is FormatException
      ? error.message
      : 'The meeting or collection request could not be loaded. Try again.';
  void _top(int generation) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_active(generation) && _scroll.hasClients) _scroll.jumpTo(0);
    });
  }

  void _fail(Object error, int generation, {bool uncertain = false}) {
    if (!mounted || !_foreground || generation != _generation) return;
    setState(() {
      if (!widget.repository.isActive ||
          error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _erasePrivate();
        _pending?.close();
        _pending = null;
      }
      _busy = false;
      _loading = false;
      _error = uncertain
          ? 'The request outcome is uncertain. Retry the original request.'
          : _message(error);
    });
    _top(generation);
  }

  bool _pendingAllowed(FamilyVisitConfiguration config) {
    final pending = _pending;
    if (pending == null) return true;
    if (pending.learnerId != null &&
        !config.children.any((c) => c.id == pending.learnerId))
      return false;
    if (widget.staffOnly && !config.canHost) return false;
    if ([
          FamilyVisitAction.approvePass,
          FamilyVisitAction.cancelMeeting,
          if (widget.staffOnly) FamilyVisitAction.cancelPass,
        ].contains(pending.action) &&
        !config.canManage)
      return false;
    if ([
          FamilyVisitAction.book,
          FamilyVisitAction.requestPass,
          FamilyVisitAction.approvePass,
        ].contains(pending.action) &&
        !config.writable)
      return false;
    return true;
  }

  Future<void> _load({bool resolveExpired = false}) async {
    if (!_foreground || _busy) return;
    final generation = ++_generation;
    setState(() {
      _erasePrivate();
      _loading = true;
    });
    try {
      final config = await widget.repository.configuration();
      if (!_accept(generation)) return;
      SchoolWallTime.local(_now(), config.timezone);
      if (widget.staffOnly && !config.canHost || !_pendingAllowed(config))
        throw ApiException(
          'Your meeting access changed. Reopen this screen.',
          statusCode: 403,
          isApiMessage: true,
        );
      if (!widget.staffOnly) {
        final pinned = _pending?.learnerId ?? _learner;
        _learner = config.children.any((c) => c.id == pinned)
            ? pinned
            : config.children.firstOrNull?.id;
      }
      final meetings = !widget.staffOnly && _learner == null
          ? null
          : await widget.repository.meetings(
              learnerId: widget.staffOnly ? null : _learner,
            );
      if (!_accept(generation)) return;
      final passes =
          (!widget.staffOnly && _learner != null ||
              widget.staffOnly && config.canManage)
          ? await widget.repository.passes(
              learnerId: widget.staffOnly ? null : _learner,
            )
          : null;
      if (!_accept(generation)) return;
      setState(() {
        _config = config;
        _meetings.addAll(meetings?.items ?? []);
        _passes.addAll(passes?.items ?? []);
        _nextMeeting = meetings?.nextPage;
        _nextPass = passes?.nextPage;
        _loading = false;
        if (resolveExpired && _pending?.expired == true) {
          _pending?.close();
          _pending = null;
        }
        if (!config.canManage && widget.staffOnly) _tab = 0;
        if (!widget.staffOnly && _pending == null) {
          final start = _now().add(const Duration(minutes: 15));
          _starts = SchoolWallTime.local(start, config.timezone);
          _ends = SchoolWallTime.local(
            start.add(
              Duration(
                minutes: config.gateRules.maxWindowMinutes < 60
                    ? config.gateRules.maxWindowMinutes
                    : 60,
              ),
            ),
            config.timezone,
          );
        }
      });
      _top(generation);
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _more(bool meetings) async {
    if (!_idle) return;
    final page = meetings ? _nextMeeting : _nextPass;
    if (page == null) return;
    final generation = _generation;
    setState(() => _loading = true);
    try {
      if (meetings) {
        final value = await widget.repository.meetings(
          learnerId: widget.staffOnly ? null : _learner,
          page: page,
        );
        if (!_accept(generation)) return;
        setState(() {
          for (final row in value.items) {
            if (!_meetings.any((old) => old.id == row.id)) _meetings.add(row);
          }
          _nextMeeting = value.nextPage;
          _loading = false;
        });
      } else {
        final value = await widget.repository.passes(
          learnerId: widget.staffOnly ? null : _learner,
          page: page,
        );
        if (!_accept(generation)) return;
        setState(() {
          for (final row in value.items) {
            if (!_passes.any((old) => old.id == row.id)) _passes.add(row);
          }
          _nextPass = value.nextPage;
          _loading = false;
        });
      }
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _perform(FamilyVisitMutation intent, {int? learner}) async {
    if (!_idle) return;
    FocusManager.instance.primaryFocus?.unfocus();
    _pending = FamilyVisitPending(
      repository: widget.repository,
      intent: intent,
      learnerId: learner,
      clock: widget.now,
    );
    _collector.clear();
    _reason.clear();
    await _sendPending();
  }

  Future<void> _sendPending() async {
    if (_busy || _loading || !_foreground || _pending == null) return;
    final pending = _pending!, generation = _generation;
    if (pending.expired) {
      setState(
        () => _error =
            'The retry copy expired. Refresh current records before starting another request.',
      );
      _top(generation);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _notice = null;
    });
    try {
      // Fresh guardian/host/manager and module authority precede decoding private intent.
      final config = await widget.repository.configuration();
      if (!_accept(generation)) return;
      if (!_pendingAllowed(config))
        throw ApiException(
          'Your meeting access changed. Reopen this screen.',
          statusCode: 403,
          isApiMessage: true,
        );
      final intent = pending.open(widget.repository, authorized: true);
      await widget.repository.execute(intent);
      if (!_accept(generation)) return;
      pending.close();
      _pending = null;
      _busy = false;
      await _load();
      if (mounted &&
          _foreground &&
          widget.repository.isActive &&
          _config != null)
        setState(() => _notice = 'Your request was saved.');
    } catch (error) {
      if (!_accept(generation)) return;
      final definitive =
          error is ApiException &&
          error.statusCode != null &&
          error.statusCode! < 500 &&
          ![408, 425, 429].contains(error.statusCode);
      if (definitive) {
        pending.close();
        _pending = null;
      }
      _fail(error, generation, uncertain: !definitive);
    }
  }

  FamilyVisitMutation _revision(
    FamilyVisitAction action,
    String target,
    int revision, {
    int? learner,
  }) => FamilyVisitMutation(
    action: action,
    target: target,
    body: {
      'operation_id': const Uuid().v4(),
      'revision': revision,
      if (learner != null) 'learner_id': learner,
    },
  );
  String _date(DateTime value) =>
      SchoolWallTime.local(value, _config!.timezone).replaceFirst('T', ' ');
  Future<void> _choose(BuildContext inner, bool start) async {
    if (!_idle || !_config!.writable) return;
    final generation = _generation, zone = _config!.timezone;
    final wall =
        (start ? _starts : _ends) ?? SchoolWallTime.local(_now(), zone);
    final initial = DateTime.parse(wall),
        today = DateTime.parse(
          SchoolWallTime.local(_now(), zone).substring(0, 10),
        );
    final date = await showDatePicker(
      context: inner,
      useRootNavigator: false,
      initialDate: initial,
      firstDate: today,
      lastDate: today.add(Duration(days: _config!.gateRules.maxFutureDays + 1)),
    );
    if (date == null || !_active(generation) || !inner.mounted) return;
    final time = await showTimePicker(
      context: inner,
      useRootNavigator: false,
      initialTime: TimeOfDay(hour: initial.hour, minute: initial.minute),
    );
    if (time == null || !_active(generation)) return;
    String pad(int n) => '$n'.padLeft(2, '0');
    final value =
        '${date.year}-${pad(date.month)}-${pad(date.day)}T${pad(time.hour)}:${pad(time.minute)}';
    try {
      SchoolWallTime.utc(value, zone);
      setState(() {
        if (start)
          _starts = value;
        else
          _ends = value;
        _error = null;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  void _request() {
    if (!_canRequest) return;
    try {
      final start = SchoolWallTime.utc(_starts!, _config!.timezone),
          end = SchoolWallTime.utc(_ends!, _config!.timezone);
      if (!end.isAfter(start) ||
          end.difference(start).inMinutes > _config!.gateRules.maxWindowMinutes)
        throw FormatException(
          l('Choose a collection window within the school rules.'),
        );
      _perform(
        FamilyVisitMutation(
          action: FamilyVisitAction.requestPass,
          body: FamilyGateRequest(
            operationId: const Uuid().v4(),
            learnerId: _learner!,
            collector: _collector.text.trim(),
            reason: _reason.text.trim(),
            startsAt: _starts!,
            endsAt: _ends!,
          ).toJson(),
        ),
        learner: _learner,
      );
    } catch (error) {
      _fail(error, _generation);
    }
  }

  Widget _meeting(FamilyMeetingSlot row) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(row.title, style: Theme.of(context).textTheme.titleMedium),
          Text('${row.hostName} · ${row.venue}'),
          Text('${_date(row.startsAt)} – ${_date(row.endsAt)}'),
          Text(l(_status(row.status))),
          if (row.learnerName != null) Text(row.learnerName!),
          if (!widget.staffOnly &&
              row.status == 'open' &&
              row.bookingClosesAt.isAfter(_now()))
            FilledButton(
              key: ValueKey('visit-book-${row.id}'),
              onPressed: _idle && _config!.writable
                  ? () => _perform(
                      _revision(
                        FamilyVisitAction.book,
                        row.id,
                        row.revision,
                        learner: _learner,
                      ),
                      learner: _learner,
                    )
                  : null,
              child: Text(l('Book this meeting')),
            ),
          if (!widget.staffOnly &&
              row.status == 'booked' &&
              row.guardianUserId == widget.repository.accountId)
            OutlinedButton(
              key: ValueKey('visit-unbook-${row.id}'),
              onPressed: _idle
                  ? () => _perform(
                      _revision(
                        FamilyVisitAction.unbook,
                        row.id,
                        row.revision,
                        learner: _learner,
                      ),
                      learner: _learner,
                    )
                  : null,
              child: Text(l('Cancel my booking')),
            ),
          if (widget.staffOnly &&
              _config!.canManage &&
              row.status != 'cancelled')
            OutlinedButton(
              onPressed: _idle
                  ? () => _perform(
                      _revision(
                        FamilyVisitAction.cancelMeeting,
                        row.id,
                        row.revision,
                      ),
                    )
                  : null,
              child: Text(l('Cancel meeting slot')),
            ),
        ],
      ),
    ),
  );
  String _status(String value) => switch (value) {
    'open' => 'Available',
    'booked' => 'Booked',
    'pending' => 'Awaiting staff approval',
    'approved' => 'Approved for the collection window',
    'collected' => 'Collection recorded',
    'cancelled' => 'Cancelled',
    _ => 'Refresh',
  };
  Widget _pass(FamilyGatePass row) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (row.learnerName != null)
            Text(
              row.learnerName!,
              style: Theme.of(context).textTheme.titleMedium,
            ),
          Text('${l('Named collector')}: ${row.collector}'),
          Text(row.reason),
          Text('${_date(row.startsAt)} – ${_date(row.endsAt)}'),
          Text(l(_status(row.status))),
          if (widget.staffOnly && _config!.canManage && row.status == 'pending')
            FilledButton(
              key: ValueKey('visit-approve-${row.id}'),
              onPressed:
                  _idle &&
                      _config!.writable &&
                      row.requestedBy != widget.repository.accountId
                  ? () => _perform(
                      _revision(
                        FamilyVisitAction.approvePass,
                        row.id,
                        row.revision,
                      ),
                    )
                  : null,
              child: Text(l('Approve this collection request')),
            ),
          if (['pending', 'approved'].contains(row.status))
            OutlinedButton(
              key: ValueKey('visit-cancel-${row.id}'),
              onPressed: _idle
                  ? () => _perform(
                      _revision(
                        FamilyVisitAction.cancelPass,
                        row.id,
                        row.revision,
                      ),
                      learner: widget.staffOnly ? null : _learner,
                    )
                  : null,
              child: Text(l('Cancel collection request')),
            ),
        ],
      ),
    ),
  );
  Widget _form(BuildContext inner) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        l('Request early collection'),
        style: Theme.of(context).textTheme.titleMedium,
      ),
      Text(
        l(
          'A request needs staff approval. Gate staff will check the named collector in person.',
        ),
      ),
      TextField(
        key: const ValueKey('visit-collector'),
        controller: _collector,
        enabled: _idle && _config!.writable,
        maxLength: 191,
        decoration: InputDecoration(labelText: l('Named collector')),
        onChanged: (_) => setState(() {}),
      ),
      TextField(
        key: const ValueKey('visit-reason'),
        controller: _reason,
        enabled: _idle && _config!.writable,
        minLines: 2,
        maxLines: 5,
        maxLength: 2000,
        decoration: InputDecoration(labelText: l('Collection reason')),
        onChanged: (_) => setState(() {}),
      ),
      ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(l('Collection starts')),
        subtitle: Text(_starts?.replaceFirst('T', ' ') ?? ''),
        trailing: IconButton(
          tooltip: l('Choose collection start'),
          onPressed: _idle && _config!.writable
              ? () => _choose(inner, true)
              : null,
          icon: const Icon(Icons.edit_calendar_outlined),
        ),
      ),
      ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(l('Collection ends')),
        subtitle: Text(_ends?.replaceFirst('T', ' ') ?? ''),
        trailing: IconButton(
          tooltip: l('Choose collection end'),
          onPressed: _idle && _config!.writable
              ? () => _choose(inner, false)
              : null,
          icon: const Icon(Icons.edit_calendar_outlined),
        ),
      ),
      FilledButton(
        key: const ValueKey('visit-request'),
        onPressed: _canRequest ? _request : null,
        child: Text(l('Request staff approval')),
      ),
    ],
  );
  Widget _body(BuildContext inner) => ScreenScaffold(
    title: l('Meetings and early collection'),
    body: SingleChildScrollView(
      controller: _scroll,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_loading || _busy) const LinearProgressIndicator(),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(l(_error!), key: const ValueKey('visit-error')),
            ),
          if (_notice != null)
            Text(l(_notice!), key: const ValueKey('visit-saved')),
          if (_pending != null && !_pending!.expired)
            FilledButton(
              key: const ValueKey('visit-retry'),
              onPressed: !_loading && !_busy && _config != null
                  ? _sendPending
                  : null,
              child: Text(l('Retry original request')),
            ),
          if (_pending?.expired == true)
            Text(
              l(
                'The retry copy expired. Refresh current records before starting another request.',
              ),
            ),
          if (!_busy)
            TextButton(
              onPressed: () => _load(resolveExpired: true),
              child: Text(l('Refresh current records')),
            ),
          if (_config != null) ...[
            Text('${l('Times use the school timezone.')} ${_config!.timezone}'),
            if (!_config!.writable)
              Text(
                l(
                  'This module is read only. Existing requests can be cancelled.',
                ),
              ),
            if (!widget.staffOnly)
              DropdownButtonFormField<int>(
                key: ValueKey('visit-child-$_learner'),
                initialValue: _learner,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('Learner')),
                items: [
                  for (final child in _config!.children)
                    DropdownMenuItem(
                      value: child.id,
                      child: Text(child.name, overflow: TextOverflow.ellipsis),
                    ),
                ],
                onChanged: _idle
                    ? (id) {
                        _learner = id;
                        _load();
                      }
                    : null,
              ),
            if (!widget.staffOnly && _config!.children.isEmpty)
              Text(l('No approved current learners are available.')),
            if (widget.staffOnly || _learner != null) ...[
              if (!widget.staffOnly || _config!.canManage)
                Wrap(
                  spacing: 8,
                  children: [
                    ChoiceChip(
                      label: Text(l('Meeting appointments')),
                      selected: _tab == 0,
                      onSelected: _busy
                          ? null
                          : (_) => setState(() => _tab = 0),
                    ),
                    ChoiceChip(
                      label: Text(l('Early collection')),
                      selected: _tab == 1,
                      onSelected: _busy
                          ? null
                          : (_) => setState(() => _tab = 1),
                    ),
                  ],
                ),
              if (_tab == 0) ...[
                if (_meetings.isEmpty && !_loading)
                  Text(l('No meeting appointments in this scope.')),
                for (final row in _meetings) _meeting(row),
                if (_nextMeeting != null)
                  TextButton(
                    onPressed: _idle ? () => _more(true) : null,
                    child: Text(l('More meeting slots')),
                  ),
              ],
              if (_tab == 1) ...[
                if (!widget.staffOnly) _form(inner),
                for (final row in _passes) _pass(row),
                if (_passes.isEmpty && !_loading)
                  Text(l('No current collection requests.')),
                if (_nextPass != null)
                  TextButton(
                    onPressed: _idle ? () => _more(false) : null,
                    child: Text(l('More collection requests')),
                  ),
              ],
            ],
          ],
        ],
      ),
    ),
  );
  @override
  Widget build(BuildContext context) {
    if (!_foreground || !widget.repository.isActive)
      return ScreenScaffold(
        title: l('Meetings and early collection'),
        body: Center(
          child: Text(l('Your meeting access changed. Reopen this screen.')),
        ),
      );
    return NavigatorPopHandler<void>(
      onPopWithResult: (_) => _navigator.currentState?.maybePop(),
      child: Navigator(
        key: _navigator,
        onGenerateRoute: (_) => MaterialPageRoute<void>(builder: _body),
      ),
    );
  }
}
