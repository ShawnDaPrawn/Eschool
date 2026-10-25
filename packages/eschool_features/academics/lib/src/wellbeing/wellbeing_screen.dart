import 'wellbeing_inbox_panel.dart';
import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'school_wall_time.dart';
import 'wellbeing_repository.dart';
import 'private_report_image.dart';

/// Clinical bodies and unsent drafts exist only in this authorization generation.
class WellbeingScreen extends StatefulWidget {
  const WellbeingScreen({required this.repository, super.key});
  final WellbeingRepository repository;
  @override
  State<WellbeingScreen> createState() => _WellbeingScreenState();
}

class _WellbeingScreenState extends State<WellbeingScreen>
    with WidgetsBindingObserver {
  WellbeingRepository get repo => widget.repository;
  WellbeingAccess? _access;
  WellbeingCasePage? _cases;
  WellbeingReportPage? _reports;
  WellbeingCase? _case;
  WellbeingReport? _report;
  WellbeingCaseChoicePage? _professionals, _incidents;
  List<WellbeingAppointment> _appointments = [];
  final _note = TextEditingController(),
      _when = TextEditingController(),
      _reason = TextEditingController(),
      _response = TextEditingController(),
      _start = TextEditingController(),
      _end = TextEditingController(),
      _location = TextEditingController(),
      _appointmentNotes = TextEditingController(),
      _search = TextEditingController(),
      _from = TextEditingController(),
      _to = TextEditingController();
  int _epoch = 0,
      _casePage = 1,
      _reportPage = 1,
      _professionalPage = 1,
      _incidentPage = 1;
  int? _correction, _professional, _incident, _editingAppointment;
  String _kind = 'note',
      _caseStatus = 'open',
      _reportStatus = 'open',
      _appointmentStatus = 'scheduled';
  bool _inbox = false;
  bool _busy = true,
      _hidden = true,
      _blocked = false,
      _internal = true,
      _conflict = false,
      _reviewed = false;
  String? _message, _pending, _pendingAction;
  int? _pendingOwner, _pendingAppointment;
  bool get _active => mounted && repo.isActive;
  bool get _editable =>
      !_busy && !_hidden && !_blocked && _pending == null && repo.isActive;
  bool get _hasPrivateDraft => [
    _note,
    _reason,
    _response,
    _location,
    _appointmentNotes,
  ].any((c) => c.text.trim().isNotEmpty);
  void _requireComparison() {
    _conflict = true;
    _reviewed = false;
    _message = l(
      'The current record changed. Compare it with your draft before saving again.',
    );
  }

  bool get _write =>
      _editable && _access?.canWrite == true && (!_conflict || _reviewed);
  String l(String key) => academicText(context, key);
  List<TextEditingController> get _controllers => [
    _note,
    _when,
    _reason,
    _response,
    _start,
    _end,
    _location,
    _appointmentNotes,
    _search,
    _from,
    _to,
  ];
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _bootstrap();
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    for (final c in _controllers) {
      c.clear();
      c.dispose();
    }
    super.dispose();
  }

  void _erase() {
    _inbox = false;
    _access = null;
    _cases = null;
    _reports = null;
    _case = null;
    _report = null;
    _professionals = null;
    _incidents = null;
    _appointments = [];
    _pending = null;
    _pendingAction = null;
    _pendingOwner = null;
    _pendingAppointment = null;
    _correction = null;
    _professional = null;
    _incident = null;
    _editingAppointment = null;
    _conflict = false;
    _reviewed = false;
    for (final c in _controllers) {
      c.clear();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted)
        setState(() {
          _hidden = true;
          _busy = false;
          _erase();
        });
    } else {
      _bootstrap();
    }
  }

  Future<void> _run(Future<void> Function(int) task) async {
    if (!_active) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await task(epoch);
    } catch (error) {
      if (!_current(epoch)) return;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _erase();
        _blocked = true;
        _message = l(
          'Your clinical access changed. Reopen this area after refreshing your account.',
        );
      } else {
        _message = error is FormatException
            ? l(error.message)
            : l(
                'The request could not be completed. Check the current record before retrying.',
              );
      }
    } finally {
      if (_current(epoch)) setState(() => _busy = false);
    }
  }

  bool _current(int epoch) => _active && epoch == _epoch;
  Future<void> _bootstrap() async {
    if (!mounted) return;
    setState(() {
      _erase();
      _hidden = true;
      _blocked = false;
    });
    await _run((epoch) async {
      final access = await repo.access();
      if (!access.professional)
        throw ApiException('Forbidden', statusCode: 403);
      final cases = access.canCasework ? await repo.cases(_casePage) : null;
      final reports = access.canTriage ? await repo.reports(_reportPage) : null;
      if (!_current(epoch)) return;
      _access = access;
      _cases = cases;
      _reports = reports;
      _hidden = false;
      _from.text = access.today;
      _to.text = DateTime.parse(
        access.today,
      ).add(const Duration(days: 30)).toIso8601String().substring(0, 10);
      if (access.timezone != null) {
        _when.text = SchoolWallTime.local(DateTime.now(), access.timezone!);
        _start.text = '${access.today}T10:00';
        _end.text = '${access.today}T10:30';
      }
    });
  }

  Future<void> _openCase(int id, {int? before, bool preserve = false}) => _run((
    epoch,
  ) async {
    final previousCase = _case;
    final previousAppointment = _appointments
        .where((row) => row.id == _editingAppointment)
        .firstOrNull;
    final hadDraft = preserve && _hasPrivateDraft;
    if (!preserve) {
      for (final c in [
        _note,
        _reason,
        _response,
        _location,
        _appointmentNotes,
        _search,
      ]) {
        c.clear();
      }
      _professional = null;
      _incident = null;
      _editingAppointment = null;
      _appointmentStatus = 'scheduled';
      _kind = 'note';
    }
    final row = await repo.caseRecord(id, before: before);
    final day = _start.text.length >= 10
        ? _start.text.substring(0, 10)
        : _access!.today;
    final people = await repo.professionals(
      id,
      day,
      search: _search.text,
      page: _professionalPage,
      selected: _professional,
    );
    final incidents = await repo.incidentChoices(
      id,
      page: _incidentPage,
      selected: _incident,
    );
    final appointments = await repo.appointments(id, _from.text, _to.text);
    if (!_current(epoch)) return;
    if (!preserve) {
      _note.clear();
      _reason.clear();
      _correction = null;
      _conflict = false;
      _reviewed = false;
    }
    final currentAppointment = appointments
        .where((item) => item.id == _editingAppointment)
        .firstOrNull;
    if (hadDraft &&
        ((previousCase?.id == id && previousCase?.revision != row.revision) ||
            (previousAppointment != null &&
                currentAppointment?.revision !=
                    previousAppointment.revision))) {
      _requireComparison();
    }
    _case = row;
    _report = null;
    if (!preserve) _caseStatus = row.status.value;
    _professionals = people;
    _incidents = incidents;
    _appointments = appointments;
    if (!people.items.any((x) => x.id == _professional)) _professional = null;
    if (!incidents.items.any((x) => x.id == _incident)) _incident = null;
  });
  Future<void> _openReport(int id, {int? before, bool preserve = false}) =>
      _run((epoch) async {
        final previous = _report;
        final hadDraft = preserve && _response.text.trim().isNotEmpty;
        final row = await repo.report(id, before: before);
        if (!_current(epoch)) return;
        if (!preserve) {
          _response.clear();
          _internal = true;
          _conflict = false;
          _reviewed = false;
        }
        if (hadDraft &&
            previous?.id == id &&
            previous?.revision != row.revision) {
          _requireComparison();
        }
        _report = row;
        _case = null;
        if (!preserve) _reportStatus = row.status.value;
      });
  Future<void> _save(
    String action,
    int owner,
    Map<String, dynamic> input, {
    int? appointment,
  }) async {
    if (!_write) return;
    _pending = jsonEncode({...input, 'operation_id': const Uuid().v4()});
    _pendingAction = action;
    _pendingOwner = owner;
    _pendingAppointment = appointment;
    await _retry();
  }

  Future<void> _retry() async {
    final raw = _pending,
        action = _pendingAction,
        id = _pendingOwner,
        appointment = _pendingAppointment;
    if (raw == null || action == null || id == null || !_active || _busy)
      return;
    bool saved = false, compare = false;
    await _run((epoch) async {
      try {
        await repo.write(
          action,
          id,
          Map<String, dynamic>.from(jsonDecode(raw)),
          appointment: appointment,
        );
        if (!_current(epoch)) return;
        _pending = null;
        _conflict = false;
        _reviewed = false;
        saved = true;
      } on ApiException catch (error) {
        if (!_current(epoch)) return;
        if (error.statusCode == 409) {
          _pending = null;
          _conflict = true;
          _reviewed = false;
          compare = true;
        } else if (error.statusCode == 422) {
          _pending = null;
          _message = l(
            'Check the entered values. Your unsent draft remains here.',
          );
        } else {
          rethrow;
        }
      }
    });
    if (!_active || _blocked) return;
    if (saved || compare) {
      if (action == 'respond') {
        await _openReport(id, preserve: compare);
      } else {
        await _openCase(id, preserve: compare);
      }
      if (!_active || _blocked) return;
      setState(() {
        _message = l(
          saved
              ? 'Saved. The current record is shown.'
              : 'The current record changed. Compare it with your draft before saving again.',
        );
        if (saved) {
          _note.clear();
          _response.clear();
          _reason.clear();
          _location.clear();
          _appointmentNotes.clear();
          _editingAppointment = null;
        }
      });
    }
  }

  Widget _field(
    String label,
    TextEditingController c, {
    int lines = 1,
    int max = 20000,
    bool? enabled,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: c,
      enabled: enabled ?? _write,
      maxLines: lines,
      maxLength: max,
      decoration: InputDecoration(
        labelText: l(label),
        border: const OutlineInputBorder(),
      ),
    ),
  );
  Widget _button(String label, VoidCallback? action) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: FilledButton(onPressed: action, child: Text(l(label))),
  );
  Widget _select<T>(
    String label,
    T? value,
    List<DropdownMenuItem<T>> items,
    ValueChanged<T?> change,
  ) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: DropdownButtonFormField<T>(
      key: ValueKey('$label:$value:${items.map((e) => e.value).join(',')}'),
      initialValue: items.any((x) => x.value == value) ? value : null,
      decoration: InputDecoration(
        labelText: l(label),
        border: const OutlineInputBorder(),
      ),
      isExpanded: true,
      items: items,
      onChanged: _write ? change : null,
    ),
  );
  List<DropdownMenuItem<String>> _options(List<String> values) =>
      values.map((v) => DropdownMenuItem(value: v, child: Text(l(v)))).toList();
  Widget _title(String text) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 12),
    child: Text(l(text), style: Theme.of(context).textTheme.titleLarge),
  );
  Widget _pages(int page, int last, void Function(int) load) => Wrap(
    spacing: 12,
    children: [
      Text('${l('Page')} $page / $last'),
      TextButton(
        onPressed: _editable && page > 1 ? () => load(page - 1) : null,
        child: Text(l('Previous')),
      ),
      TextButton(
        onPressed: _editable && page < last ? () => load(page + 1) : null,
        child: Text(l('Next')),
      ),
    ],
  );
  String _stamp(DateTime value) {
    try {
      return SchoolWallTime.local(
        value,
        _access!.timezone!,
      ).replaceFirst('T', ' ');
    } catch (_) {
      return value.toUtc().toIso8601String();
    }
  }

  Future<void> _entry() async {
    try {
      await _save('entry', _case!.id, {
        'revision': _case!.revision,
        'kind': _kind,
        'body': _note.text,
        'happened_at': SchoolWallTime.utc(
          _when.text,
          _access!.timezone!,
        ).toIso8601String(),
        if (_correction != null) 'supersedes_id': _correction,
      });
    } on FormatException catch (error) {
      if (mounted) setState(() => _message = l(error.message));
    }
  }

  Future<void> _appointment() async {
    try {
      final start = SchoolWallTime.utc(_start.text, _access!.timezone!),
          end = SchoolWallTime.utc(_end.text, _access!.timezone!);
      final old = _appointments
          .where((row) => row.id == _editingAppointment)
          .firstOrNull;
      await _save('appointment', _case!.id, {
        'revision': old?.revision ?? 0,
        'professional_user_id': _professional,
        'starts_at': start.toIso8601String(),
        'ends_at': end.toIso8601String(),
        'location': _location.text,
        'notes': _appointmentNotes.text,
        'status': _appointmentStatus,
      }, appointment: _editingAppointment);
    } on FormatException catch (error) {
      if (mounted) setState(() => _message = l(error.message));
    }
  }

  List<Widget> _caseView() {
    final row = _case!, zone = _access!.timezone;
    return [
      _title('${row.learnerName} · ${l('Counselling case')}'),
      Text('${l('Status')}: ${l(row.status.value)}'),
      for (final entry in row.entries)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${l(entry.kind.value)} · ${entry.authorName}'),
                Text(_stamp(entry.happenedAt)),
                SelectableText(entry.body),
                if (entry.supersedesId != null)
                  Text(
                    l(
                      'This entry corrects an earlier note; the original remains in the history.',
                    ),
                  ),
              ],
            ),
          ),
        ),
      if (row.hasOlder)
        _button(
          'Read older case entries',
          _editable
              ? () => _openCase(row.id, before: row.nextBefore, preserve: true)
              : null,
        ),
      TextButton(
        onPressed: _editable ? () => _openCase(row.id, preserve: true) : null,
        child: Text(l('Show latest entries')),
      ),
      _title('Add case note or escalation'),
      if (zone == null)
        Text(
          l(
            'Refresh your school settings before entering appointment or note times.',
          ),
        ),
      _select(
        'Entry type',
        _kind,
        _options(['note', 'escalation']),
        (v) => setState(() => _kind = v!),
      ),
      _field(
        'When it happened (YYYY-MM-DDTHH:MM)',
        _when,
        max: 16,
        enabled: _write && zone != null,
      ),
      _field('Private case note', _note, lines: 5),
      _select<int>('Correct one of my earlier notes (optional)', _correction, [
        DropdownMenuItem<int>(value: null, child: Text(l('New entry'))),
        for (final e in row.entries.where(
          (e) =>
              e.authorId == repo.accountId &&
              ['note', 'escalation'].contains(e.kind.value),
        ))
          DropdownMenuItem(
            value: e.id,
            child: Text(
              '${_stamp(e.happenedAt)} · ${e.body}',
              overflow: TextOverflow.ellipsis,
            ),
          ),
      ], (v) => setState(() => _correction = v)),
      _button(
        'Save private case entry',
        _write && zone != null ? _entry : null,
      ),
      _title('Close or reopen case'),
      _select(
        'Case status',
        _caseStatus,
        _options(['open', 'closed']),
        (v) => setState(() => _caseStatus = v!),
      ),
      _field('Reason for this change', _reason, lines: 2, max: 2000),
      _button(
        'Save case status',
        _write
            ? () => _save('status', row.id, {
                'revision': row.revision,
                'status': _caseStatus,
                'reason': _reason.text,
              })
            : null,
      ),
      _title('Link an identified incident'),
      Text(
        l(
          'Only identified reports for this learner within your separate safeguarding-team scope can be linked.',
        ),
      ),
      _select<int>('Identified incident', _incident, [
        for (final item in _incidents?.items ?? <WellbeingNamedChoice>[])
          DropdownMenuItem(
            value: item.id,
            child: Text(item.name, overflow: TextOverflow.ellipsis),
          ),
      ], (v) => setState(() => _incident = v)),
      if (_incidents != null)
        _pages(_incidentPage, _incidents!.lastPage, (page) {
          _incidentPage = page;
          _openCase(row.id, preserve: true);
        }),
      _button(
        'Link incident',
        _write && _incident != null
            ? () => _save('link', row.id, {
                'revision': row.revision,
                'incident_report_id': _incident,
              })
            : null,
      ),
      _title('Private appointments'),
      Text('${l('School timezone')}: ${zone ?? l('Unknown')}'),
      _field('Calendar from (YYYY-MM-DD)', _from, max: 10, enabled: _editable),
      _field('Calendar to (YYYY-MM-DD)', _to, max: 10, enabled: _editable),
      _button(
        'Show appointments',
        _editable ? () => _openCase(row.id, preserve: true) : null,
      ),
      for (final item in _appointments)
        Card(
          child: ListTile(
            title: Text(
              '${_stamp(item.startsAt)} – ${_stamp(item.endsAt)} · ${l(item.status.value)}',
            ),
            subtitle: Text('${item.location}\n${item.notes}'),
            isThreeLine: true,
            trailing: IconButton(
              tooltip: l('Edit private appointment'),
              onPressed: _write && zone != null
                  ? () {
                      setState(() {
                        _editingAppointment = item.id;
                        _professional = item.professionalUserId;
                        _start.text = SchoolWallTime.local(item.startsAt, zone);
                        _end.text = SchoolWallTime.local(item.endsAt, zone);
                        _location.text = item.location;
                        _appointmentNotes.text = item.notes;
                        _appointmentStatus = item.status.value;
                      });
                      _openCase(row.id, preserve: true);
                    }
                  : null,
              icon: const Icon(Icons.edit_outlined),
            ),
          ),
        ),
      if (_appointments.isEmpty)
        Text(l('No appointments in this calendar window.')),
      if (_appointments.length == 1000)
        Text(
          l(
            'This calendar window reached its display limit. Narrow the dates to see the remaining appointments.',
          ),
        ),
      _title(
        _editingAppointment == null
            ? 'New private appointment'
            : 'Edit private appointment',
      ),
      _field(
        'Appointment starts (YYYY-MM-DDTHH:MM)',
        _start,
        max: 16,
        enabled: _write && zone != null,
      ),
      _field(
        'Appointment ends (YYYY-MM-DDTHH:MM)',
        _end,
        max: 16,
        enabled: _write && zone != null,
      ),
      _select<int>('Appointment professional', _professional, [
        for (final person in _professionals?.items ?? <WellbeingNamedChoice>[])
          DropdownMenuItem(
            value: person.id,
            child: Text(person.name, overflow: TextOverflow.ellipsis),
          ),
      ], (v) => setState(() => _professional = v)),
      _field(
        'Find assigned professional',
        _search,
        max: 100,
        enabled: _editable,
      ),
      _button(
        'Search assigned professionals',
        _editable
            ? () {
                _professionalPage = 1;
                _openCase(row.id, preserve: true);
              }
            : null,
      ),
      if (_professionals != null)
        _pages(_professionalPage, _professionals!.lastPage, (page) {
          _professionalPage = page;
          _openCase(row.id, preserve: true);
        }),
      Text(
        l('Refresh professional choices after changing the appointment date.'),
      ),
      _field('Appointment location', _location, max: 500),
      _field('Appointment notes', _appointmentNotes, lines: 3),
      _select(
        'Appointment status',
        _appointmentStatus,
        _options(['scheduled', 'completed', 'cancelled']),
        (v) => setState(() => _appointmentStatus = v!),
      ),
      _button(
        _editingAppointment == null
            ? 'Book private appointment'
            : 'Update private appointment',
        _write && zone != null && _professional != null ? _appointment : null,
      ),
      if (_editingAppointment != null)
        TextButton(
          onPressed: _editable
              ? () => setState(() {
                  _editingAppointment = null;
                  _location.clear();
                  _appointmentNotes.clear();
                  _appointmentStatus = 'scheduled';
                })
              : null,
          child: Text(l('New private appointment')),
        ),
    ];
  }

  List<Widget> _reportView() {
    final row = _report!;
    return [
      _title(row.categoryName),
      Text('${l('Status')}: ${l(row.status.value)}'),
      Text(
        row.anonymous
            ? l('Anonymous report')
            : row.reporterName ?? l('Identified report'),
      ),
      SelectableText(row.body),
      if (row.photos?.isNotEmpty == true) _title('Report photos'),
      for (final photo in row.photos ?? <IncidentPhoto>[])
        PrivateReportImage(
          key: ValueKey('staff-report-photo-${row.id}-${photo.id}'),
          base64: photo.thumbnailBase64,
          label: l('Photo attached to this report'),
        ),
      for (final thread in row.threads)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${thread.authorName} · ${_stamp(thread.createdAt)}'),
                if (thread.internal) Text(l('Internal safeguarding note')),
                SelectableText(thread.body),
              ],
            ),
          ),
        ),
      if (row.hasOlder == true)
        _button(
          'Read older replies',
          _editable
              ? () =>
                    _openReport(row.id, before: row.nextBefore, preserve: true)
              : null,
        ),
      TextButton(
        onPressed: _editable ? () => _openReport(row.id, preserve: true) : null,
        child: Text(l('Show latest entries')),
      ),
      _field('Triage note or reply', _response, lines: 5),
      CheckboxListTile(
        title: Text(l('Keep this note internal to the safeguarding team')),
        value: _internal,
        onChanged: _write ? (v) => setState(() => _internal = v!) : null,
      ),
      _select(
        'Report status',
        _reportStatus,
        _options(['open', 'triaged', 'resolved']),
        (v) => setState(() => _reportStatus = v!),
      ),
      _button(
        'Save triage response',
        _write
            ? () => _save('respond', row.id, {
                'revision': row.revision,
                'body': _response.text,
                'internal': _internal,
                'status': _reportStatus,
              })
            : null,
      ),
    ];
  }

  Future<void> _returnFromInbox() async {
    if (!_active || _busy) return;
    await _run((epoch) async {
      final access = await repo.access();
      if (!access.professional)
        throw ApiException('Forbidden', statusCode: 403);
      if (_current(epoch)) _access = access;
    });
    if (!_active || _blocked || _hidden || _message != null) return;
    if (_case != null) {
      await _openCase(_case!.id, preserve: true);
    } else if (_report != null) {
      await _openReport(_report!.id, preserve: true);
    } else {
      await _run((epoch) async {
        final access = await repo.access();
        if (!access.professional)
          throw ApiException('Forbidden', statusCode: 403);
        final cases = access.canCasework ? await repo.cases(_casePage) : null;
        final reports = access.canTriage
            ? await repo.reports(_reportPage)
            : null;
        if (!_current(epoch)) return;
        _access = access;
        _cases = cases;
        _reports = reports;
      });
    }
    if (_active && !_blocked && !_hidden && _message == null)
      setState(() => _inbox = false);
  }

  void _inboxDenied() {
    if (!mounted) return;
    setState(() {
      _erase();
      _blocked = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!repo.isActive || _hidden || _blocked)
      return ScreenScaffold(
        title: l('Incidents and counselling'),
        body: Center(
          child: _busy
              ? const CircularProgressIndicator()
              : Text(
                  _message ??
                      l(
                        'Your clinical access changed. Reopen this area after refreshing your account.',
                      ),
                ),
        ),
      );
    if (_inbox)
      return ScreenScaffold(
        title: l('Private updates'),
        body: WellbeingInboxPanel(
          repository: repo,
          onReturn: _returnFromInbox,
          onDenied: _inboxDenied,
        ),
      );
    return ScreenScaffold(
      title: l('Incidents and counselling'),
      action: IconButton(
        tooltip: l('Private updates'),
        onPressed: _editable ? () => setState(() => _inbox = true) : null,
        icon: const Icon(Icons.notifications_outlined),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(_access?.emergencyGuidance ?? ''),
          Text(
            l(
              'Clinical records are limited to assigned counsellors and safeguarding staff. Configuration does not grant access to report or case contents.',
            ),
          ),
          if (_busy) const LinearProgressIndicator(),
          if (_message != null)
            Semantics(liveRegion: true, child: Text(_message!)),
          if (_access?.canWrite != true)
            Text(
              l(
                'Changes are unavailable. Check the current school year and module access.',
              ),
            ),
          if (_pending != null) ...[
            Text(
              l(
                'The result is unknown. Retry the same request without changing its contents.',
              ),
            ),
            _button('Retry unchanged request', _busy ? null : _retry),
          ],
          if (_conflict)
            CheckboxListTile(
              title: Text(
                l(
                  'I compared my draft with the current record and want to save it',
                ),
              ),
              value: _reviewed,
              onChanged: !_busy ? (v) => setState(() => _reviewed = v!) : null,
            ),
          TextButton(
            onPressed: _editable ? _bootstrap : null,
            child: Text(l('Refresh clinical access')),
          ),
          if (_reports != null) ...[
            _title('My incident queue'),
            for (final row in _reports!.items)
              ListTile(
                title: Text(row.categoryName),
                subtitle: Text(
                  '${l(row.urgency.value)} · ${l(row.status.value)}',
                ),
                onTap: _editable ? () => _openReport(row.id) : null,
              ),
            _pages(_reportPage, _reports!.lastPage, (page) {
              _reportPage = page;
              _bootstrap();
            }),
          ],
          if (_cases != null) ...[
            _title('My counselling cases'),
            for (final row in _cases!.items)
              ListTile(
                title: Text(row.learnerName),
                subtitle: Text(l(row.status.value)),
                onTap: _editable
                    ? () {
                        _professionalPage = 1;
                        _incidentPage = 1;
                        _openCase(row.id);
                      }
                    : null,
              ),
            _pages(_casePage, _cases!.lastPage, (page) {
              _casePage = page;
              _bootstrap();
            }),
          ],
          if (_cases == null && _reports == null)
            Text(l('No current safeguarding or counselling assignments.')),
          if (_case != null) ..._caseView(),
          if (_report != null) ..._reportView(),
        ],
      ),
    );
  }
}
