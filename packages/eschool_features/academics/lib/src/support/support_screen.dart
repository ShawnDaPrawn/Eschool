import 'dart:convert';
import 'dart:math' as math;
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'support_repository.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({required this.repository, super.key});
  final SupportRepository repository;
  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen>
    with WidgetsBindingObserver {
  SupportRepository get repo => widget.repository;
  final _scroll = ScrollController();
  SupportAccess? _access;
  SupportConfiguration? _configuration;
  SupportPlan? _record;
  SupportCases? _cases;
  SupportAssignments? _assignments;
  SupportTeacherList? _instructions;
  SupportReviews? _reviews;
  SupportVersions? _versions;
  SupportDisclosures? _disclosures;
  final _needs = TextEditingController(),
      _accommodations = TextEditingController(),
      _stage = TextEditingController(),
      _review = TextEditingController(),
      _instruction = TextEditingController(),
      _appointmentReason = TextEditingController(),
      _disclosureReason = TextEditingController(),
      _changeReason = TextEditingController(),
      _search = TextEditingController();
  String? _audience, _message, _pendingAction, _pendingPayload;
  String _started = '',
      _due = '',
      _reviewed = '',
      _nextReview = '',
      _expires = '',
      _assignmentStarts = '',
      _assignmentEnds = '',
      _history = 'reviews';
  int _epoch = 0, _page = 1, _historyPage = 1;
  int? _learner, _person, _baseRevision, _pendingId, _withdrawId;
  bool _busy = true,
      _hidden = true,
      _blocked = false,
      _conflict = false,
      _approved = false;
  bool get _editable =>
      !_busy &&
      !_hidden &&
      !_blocked &&
      !_conflict &&
      _pendingPayload == null &&
      repo.isActive;
  bool get _writable => _editable && _access?.canWrite == true;
  bool get _hasPlanChanges =>
      _record != null &&
      (_needs.text != _record!.needs ||
          _accommodations.text != _record!.accommodations ||
          _stage.text != _record!.siasStage ||
          _started != _record!.startedOn ||
          _due != (_record!.reviewDueOn ?? ''));

  String l(String value) => academicText(context, value);
  String _modeName(String mode) => switch (mode) {
    'professional' => 'My support cases',
    'principal' => 'Support assignments',
    'teacher' => 'My approved instructions',
    _ => 'Not available for this account',
  };
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(SupportScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(oldWidget.repository, repo)) {
      _clear();
      _load();
    }
  }

  @override
  void dispose() {
    _epoch++;
    _scroll.dispose();
    WidgetsBinding.instance.removeObserver(this);
    for (final c in [
      _needs,
      _accommodations,
      _stage,
      _review,
      _instruction,
      _appointmentReason,
      _disclosureReason,
      _changeReason,
      _search,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _epoch++;
        _hidden = true;
      });
    } else {
      _load();
    }
  }

  void _clearDraft() {
    for (final c in [
      _needs,
      _accommodations,
      _stage,
      _review,
      _instruction,
      _appointmentReason,
      _disclosureReason,
      _changeReason,
    ]) {
      c.clear();
    }
    _baseRevision = null;
    _approved = false;
    _withdrawId = null;
  }

  void _clear() {
    _epoch++;
    _clearDraft();
    _access = null;
    _configuration = null;
    _record = null;
    _cases = null;
    _assignments = null;
    _instructions = null;
    _reviews = null;
    _versions = null;
    _disclosures = null;
    _pendingPayload = _pendingAction = null;
    _pendingId = null;
    _learner = _person = null;
    _audience = null;
    _hidden = true;
    _conflict = false;
    _page = _historyPage = 1;
    _started = _due = _reviewed = _nextReview = _expires = _assignmentStarts =
        _assignmentEnds = '';
  }

  bool _current(int epoch, SupportRepository source) =>
      mounted && epoch == _epoch && identical(repo, source) && source.isActive;
  void _dates(String today) {
    if (_started.isEmpty) _started = today;
    if (_reviewed.isEmpty) _reviewed = today;
    if (_expires.isEmpty) _expires = today;
    if (_assignmentStarts.isEmpty) _assignmentStarts = today;
    if (_assignmentEnds.isEmpty) _assignmentEnds = today;
  }

  Future<void> _load({int? planId, bool fillPlan = false}) async {
    final source = repo, epoch = ++_epoch;
    final chosenPlan = planId ?? _record?.id;
    setState(() {
      _busy = true;
      _hidden = true;
      _message = null;
    });
    try {
      final access = await source.access();
      final modes = access.audiences
          .where(['professional', 'principal', 'teacher'].contains)
          .toList();
      if (modes.isEmpty) {
        throw ApiException('Not available for this account', statusCode: 403);
      }
      final mode = modes.contains(_audience) ? _audience! : modes.first;
      SupportConfiguration? configuration;
      SupportCases? cases;
      SupportAssignments? assignments;
      SupportTeacherList? instructions;
      SupportPlan? record;
      SupportReviews? reviews;
      SupportVersions? versions;
      SupportDisclosures? disclosures;
      if (mode == 'teacher') {
        instructions = await source.instructions(page: _page);
      } else {
        configuration = await source.configuration(
          mode,
          search: _search.text.trim(),
          page: _page,
        );
        if (mode == 'principal') {
          assignments = await source.assignments(page: _page);
        } else {
          cases = await source.cases(page: _page);
          if (chosenPlan != null) {
            record = await source.plan(chosenPlan);
            switch (_history) {
              case 'versions':
                versions = await source.versions(
                  chosenPlan,
                  page: _historyPage,
                );
              case 'disclosures':
                disclosures = await source.disclosures(
                  chosenPlan,
                  page: _historyPage,
                );
              default:
                reviews = await source.reviews(chosenPlan, page: _historyPage);
            }
          }
        }
      }
      if (!_current(epoch, source)) return;
      setState(() {
        if (_audience != null && mode != _audience) {
          _clearDraft();
          _pendingPayload = _pendingAction = null;
          _pendingId = null;
          _learner = _person = null;
        }
        if (record == null &&
            _learner != null &&
            configuration != null &&
            !configuration.learners.any((p) => p.id == _learner)) {
          _clearDraft();
          _learner = null;
        }
        _access = access;
        _audience = mode;
        _configuration = configuration;
        _cases = cases;
        _assignments = assignments;
        _instructions = instructions;
        _record = record;
        _reviews = reviews;
        _versions = versions;
        _disclosures = disclosures;
        _dates(access.today);
        if (configuration != null) {
          if (!configuration.staff.any((p) => p.id == _person)) {
            _person = configuration.staff.firstOrNull?.id;
            _approved = false;
          }
        }
        if (record != null && (fillPlan || _baseRevision == null)) {
          _needs.text = record.needs;
          _accommodations.text = record.accommodations;
          _stage.text = record.siasStage;
          _started = record.startedOn;
          _due = record.reviewDueOn ?? '';
          _learner = record.learnerId;
          _baseRevision = record.revision;
        }
        _hidden = false;
        _blocked = false;
        _busy = false;
      });
    } catch (error) {
      if (!mounted || epoch != _epoch || !identical(source, repo)) return;
      final denied =
          !repo.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      setState(() {
        if (denied) {
          _clear();
          _blocked = true;
        }
        _hidden = true;
        _busy = false;
        _message = denied
            ? 'Your access changed. Reopen Support plans after refreshing your account.'
            : 'Could not confirm your access. Retry to show this page.';
      });
    }
  }

  void _selectMode(String? mode) {
    if (!_editable || mode == null || mode == _audience) return;
    setState(() {
      _clearDraft();
      _record = null;
      _learner = _person = null;
      _audience = mode;
      _page = _historyPage = 1;
    });
    _load();
  }

  void _openPlan(int? id) {
    if (!_editable) return;
    setState(() {
      _clearDraft();
      _record = null;
      _learner = null;
      _historyPage = 1;
      _history = 'reviews';
    });
    _load(planId: id, fillPlan: true);
  }

  void _chooseLearner(int? id) {
    if (!_writable) return;
    setState(() {
      if (id != _learner) _clearDraft();
      _learner = id;
    });
  }

  Future<void> _start(String action, int? id, Map<String, dynamic> data) async {
    if (!_writable) return;
    _pendingAction = action;
    _pendingId = id;
    _pendingPayload = jsonEncode({...data, 'operation_id': const Uuid().v4()});
    await _sendPending();
  }

  void _revealNotice() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _scroll.hasClients) {
        _scroll.animateTo(
          0,
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendPending() async {
    if (_busy ||
        _hidden ||
        _pendingPayload == null ||
        !repo.isActive ||
        _access?.canWrite != true) {
      return;
    }
    final source = repo,
        epoch = ++_epoch,
        action = _pendingAction!,
        id = _pendingId,
        payload = _pendingPayload!;
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      final receipt = await source.write(
        action,
        id,
        Map<String, dynamic>.from(jsonDecode(payload) as Map),
      );
      if (!_current(epoch, source) || _hidden) return;
      final opened = action == 'create' ? receipt.id : _record?.id;
      final preservePlan =
          _hasPlanChanges && !['create', 'save', 'close'].contains(action);
      setState(() {
        _pendingPayload = _pendingAction = null;
        _pendingId = null;
        _busy = false;
        _conflict = false;
        if (!preservePlan) {
          _clearDraft();
        } else {
          if (action == 'review') _review.clear();
          if (action == 'disclose') {
            _instruction.clear();
            _disclosureReason.clear();
            _approved = false;
          }
          if (action == 'revoke-disclosure') {
            _withdrawId = null;
            _changeReason.clear();
          }
        }
      });
      await _load(planId: opened, fillPlan: !preservePlan);
      if (mounted &&
          !_hidden &&
          !_blocked &&
          identical(source, repo) &&
          repo.isActive) {
        setState(() => _message = 'Support plan change saved');
        _revealNotice();
      }
    } catch (error) {
      if (!mounted || epoch != _epoch || !identical(source, repo)) return;
      final denied =
          !repo.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      if (denied) {
        setState(() {
          _clear();
          _blocked = true;
          _busy = false;
          _message =
              'Your access changed. Reopen Support plans after refreshing your account.';
        });
        return;
      }
      if (error is ApiException && [409, 422].contains(error.statusCode)) {
        setState(() {
          _pendingPayload = _pendingAction = null;
          _pendingId = null;
          _busy = false;
          _conflict = error.statusCode == 409;
          _approved = false;
        });
        await _load();
        if (mounted) {
          setState(() {
            _message = error.isApiMessage
                ? error.errorMessage
                : 'Your changes were not applied. Compare the current record before trying again.';
          });
          _revealNotice();
        }
      } else {
        setState(() {
          _busy = false;
          _message =
              'The result is not confirmed. Retry the same change before editing.';
        });
        _revealNotice();
      }
    }
  }

  Future<void> _pick(String kind) async {
    if (!_writable) return;
    final value = switch (kind) {
      'started' => _started,
      'due' => _due,
      'reviewed' => _reviewed,
      'next' => _nextReview,
      'expires' => _expires,
      'starts' => _assignmentStarts,
      _ => _assignmentEnds,
    };
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.tryParse(value) ?? DateTime.parse(_access!.today),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (!mounted || selected == null || !_writable) return;
    final formatted =
        '${selected.year.toString().padLeft(4, '0')}-${selected.month.toString().padLeft(2, '0')}-${selected.day.toString().padLeft(2, '0')}';
    setState(() {
      switch (kind) {
        case 'started':
          _started = formatted;
        case 'due':
          _due = formatted;
        case 'reviewed':
          _reviewed = formatted;
        case 'next':
          _nextReview = formatted;
        case 'expires':
          _expires = formatted;
        case 'starts':
          _assignmentStarts = formatted;
        default:
          _assignmentEnds = formatted;
      }
    });
  }

  Widget _heading(String label) => Padding(
    padding: const EdgeInsets.only(top: 24, bottom: 12),
    child: Text(l(label), style: Theme.of(context).textTheme.titleLarge),
  );
  Widget _field(
    TextEditingController controller,
    String label, {
    int limit = 2000,
    int lines = 3,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: controller,
      readOnly: !_writable,
      maxLength: limit,
      minLines: lines,
      maxLines: math.max(lines, 6),
      decoration: InputDecoration(
        labelText: l(label),
        alignLabelWithHint: true,
      ),
      onChanged: (_) => setState(() {}),
    ),
  );
  Widget _dateButton(String label, String value, String kind) => OutlinedButton(
    onPressed: _writable ? () => _pick(kind) : null,
    child: Text('${l(label)}: ${value.isEmpty ? l('Not set') : value}'),
  );
  Widget _notice() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      if (_message != null)
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(l(_message!), key: const Key('support-message')),
        ),
      if (_busy) const LinearProgressIndicator(),
      if (!_busy && _hidden && !_blocked)
        FilledButton(onPressed: _load, child: Text(l('Retry'))),
      if (_pendingPayload != null && !_busy && !_hidden)
        FilledButton(
          onPressed: _access?.canWrite == true ? _sendPending : null,
          child: Text(l('Retry same change')),
        ),
      if (_conflict && !_hidden)
        CheckboxListTile(
          value: false,
          title: Text(l('I compared my draft with the current saved record.')),
          onChanged: _busy
              ? null
              : (checked) {
                  if (checked == true) {
                    setState(() {
                      _baseRevision = _record?.revision;
                      _conflict = false;
                      _message = null;
                    });
                  }
                },
        ),
    ],
  );
  Widget _learnerChoice() => DropdownButtonFormField<int>(
    key: ValueKey('learner-$_learner'),
    initialValue: _configuration!.learners.any((p) => p.id == _learner)
        ? _learner
        : null,
    isExpanded: true,
    decoration: InputDecoration(labelText: l('Learner')),
    items: [
      for (final p in _configuration!.learners)
        DropdownMenuItem(value: p.id, child: Text(p.name)),
    ],
    onChanged: _writable ? _chooseLearner : null,
  );
  Widget _personChoice(String label) => DropdownButtonFormField<int>(
    key: ValueKey('person-$_person'),
    initialValue: _configuration!.staff.any((p) => p.id == _person)
        ? _person
        : null,
    isExpanded: true,
    decoration: InputDecoration(labelText: l(label)),
    items: [
      for (final p in _configuration!.staff)
        DropdownMenuItem(value: p.id, child: Text(p.name)),
    ],
    onChanged: _writable
        ? (id) => setState(() {
            _person = id;
            _approved = false;
            _appointmentReason.clear();
          })
        : null,
  );
  Widget _pageButtons(int page, int last, void Function(int) choose) => Wrap(
    spacing: 12,
    children: [
      if (page > 1)
        OutlinedButton(
          onPressed: _editable ? () => choose(page - 1) : null,
          child: Text(l('Previous page')),
        ),
      if (page < last)
        OutlinedButton(
          onPressed: _editable ? () => choose(page + 1) : null,
          child: Text(l('Next page')),
        ),
    ],
  );
  Widget _teacherView() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _heading('My approved instructions'),
      if (_instructions!.items.isEmpty)
        Text(l('No current approved instructions.')),
      for (final row in _instructions!.items)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.learnerName,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text('${l('Expires on')}: ${row.expiresOn}'),
                const SizedBox(height: 12),
                Text(row.accommodations),
              ],
            ),
          ),
        ),
    ],
  );
  Widget _principalView() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        l(
          'Appointments grant only the named professional a dated support caseload. Assignment management does not open plan contents.',
        ),
      ),
      if (_access!.canWrite) ...[
        _heading('Appoint a support professional'),
        _learnerChoice(),
        const SizedBox(height: 12),
        _personChoice('Support professional'),
        Wrap(
          spacing: 12,
          children: [
            _dateButton('Starts on', _assignmentStarts, 'starts'),
            _dateButton('Ends on', _assignmentEnds, 'ends'),
          ],
        ),
        _field(_appointmentReason, 'Private appointment rationale'),
        FilledButton(
          onPressed:
              _writable &&
                  _learner != null &&
                  _person != null &&
                  _appointmentReason.text.trim().length >= 3
              ? () => _start('assign', null, {
                  'learner_id': _learner,
                  'user_id': _person,
                  'starts_on': _assignmentStarts,
                  'ends_on': _assignmentEnds,
                  'reason': _appointmentReason.text,
                })
              : null,
          child: Text(l('Appoint professional')),
        ),
      ],
      _heading('Support assignments'),
      if (_assignments!.items.isEmpty) Text(l('No support appointments.')),
      for (final row in _assignments!.items)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.learnerName,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(
                  '${row.professionalName} · ${row.startsOn} – ${row.endsOn}',
                ),
                Text(l(row.active ? 'Active' : 'Withdrawn')),
                if (_access!.canWrite && row.active)
                  OutlinedButton(
                    onPressed: _writable
                        ? () => setState(() {
                            _withdrawId = row.id;
                            _changeReason.clear();
                          })
                        : null,
                    child: Text(l('Withdraw appointment')),
                  ),
                if (_withdrawId == row.id && row.active) ...[
                  _field(_changeReason, 'Reason for this change'),
                  FilledButton(
                    onPressed:
                        _writable && _changeReason.text.trim().length >= 3
                        ? () => _start('revoke-assignment', row.id, {
                            'revision': row.revision,
                            'reason': _changeReason.text,
                          })
                        : null,
                    child: Text(l('Confirm withdrawal')),
                  ),
                ],
              ],
            ),
          ),
        ),
    ],
  );
  Widget _currentPlan(SupportPlan record) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${record.learnerName} · ${l('Current saved plan')}',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          Text(
            '${l('Revision')} ${record.revision} · ${l(record.state == SupportPlanStateEnum.active
                ? 'Active'
                : record.state == SupportPlanStateEnum.closed
                ? 'Closed'
                : 'Status unavailable')}',
          ),
          Text('${l('SIAS stage')}: ${record.siasStage}'),
          _heading('Support needs'),
          Text(record.needs),
          _heading('Accommodations'),
          Text(record.accommodations),
        ],
      ),
    ),
  );
  Widget _editPlan() {
    final record = _record;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _heading(record == null ? 'Start a support plan' : 'Edit support plan'),
        if (record == null) ...[
          _learnerChoice(),
          Text(l('Changing learners clears the unsaved draft.')),
        ] else
          Text(record.learnerName),
        _dateButton('Plan start date', _started, 'started'),
        _field(_stage, 'SIAS stage', limit: 100, lines: 1),
        _field(_needs, 'Support needs', limit: 20000),
        _field(_accommodations, 'Accommodations', limit: 20000),
        Wrap(
          spacing: 12,
          children: [
            _dateButton('Review due', _due, 'due'),
            if (_due.isNotEmpty)
              TextButton(
                onPressed: _writable ? () => setState(() => _due = '') : null,
                child: Text(l('Clear date')),
              ),
          ],
        ),
        Text(
          l(
            'Use the school’s recorded SIAS stage. Changing accommodations withdraws previously approved teacher instructions.',
          ),
        ),
        FilledButton(
          onPressed:
              _writable &&
                  _learner != null &&
                  _stage.text.trim().isNotEmpty &&
                  _needs.text.trim().isNotEmpty &&
                  _accommodations.text.trim().isNotEmpty
              ? () => _start(record == null ? 'create' : 'save', record?.id, {
                  'learner_id': _learner,
                  'revision': ?record == null ? null : _baseRevision,
                  'started_on': _started,
                  'review_due_on': _due.isEmpty ? null : _due,
                  'sias_stage': _stage.text,
                  'needs': _needs.text,
                  'accommodations': _accommodations.text,
                })
              : null,
          child: Text(l('Save support plan')),
        ),
      ],
    );
  }

  Widget _reviewForm(SupportPlan record) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _heading('Add a support review'),
      Wrap(
        spacing: 12,
        children: [
          _dateButton('Reviewed on', _reviewed, 'reviewed'),
          _dateButton('Next review date', _nextReview, 'next'),
          if (_nextReview.isNotEmpty)
            TextButton(
              onPressed: _writable
                  ? () => setState(() => _nextReview = '')
                  : null,
              child: Text(l('Clear date')),
            ),
        ],
      ),
      _field(_review, 'Review outcome', limit: 10000),
      FilledButton(
        onPressed: _writable && _review.text.trim().isNotEmpty
            ? () => _start('review', record.id, {
                'revision': _baseRevision ?? record.revision,
                'reviewed_on': _reviewed,
                'next_review_on': _nextReview.isEmpty ? null : _nextReview,
                'outcome': _review.text,
              })
            : null,
        child: Text(l('Add review')),
      ),
    ],
  );
  Widget _disclosureForm(SupportPlan record) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _heading('Approve teacher instructions'),
      _personChoice('Teacher'),
      _dateButton('Expires on', _expires, 'expires'),
      _field(_instruction, 'Approved classroom instructions', limit: 10000),
      _field(_disclosureReason, 'Reason for this disclosure'),
      CheckboxListTile(
        value: _approved,
        onChanged: _writable
            ? (v) => setState(() => _approved = v == true)
            : null,
        title: Text(
          l(
            'I approve only these instructions for this named teacher until the expiry date.',
          ),
        ),
      ),
      FilledButton(
        onPressed:
            _writable &&
                _approved &&
                _person != null &&
                _instruction.text.trim().isNotEmpty &&
                _disclosureReason.text.trim().length >= 3
            ? () => _start('disclose', record.id, {
                'revision': _baseRevision ?? record.revision,
                'user_id': _person,
                'expires_on': _expires,
                'accommodations': _instruction.text,
                'reason': _disclosureReason.text,
                'confirmation': true,
              })
            : null,
        child: Text(l('Approve instructions')),
      ),
    ],
  );
  Widget _historyView(SupportPlan record) {
    final page = _reviews?.page ?? _versions?.page ?? _disclosures?.page ?? 1,
        last =
            _reviews?.lastPage ??
            _versions?.lastPage ??
            _disclosures?.lastPage ??
            1;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _heading('Support history'),
        DropdownButtonFormField<String>(
          initialValue: _history,
          isExpanded: true,
          decoration: InputDecoration(labelText: l('Choose support history')),
          items: [
            for (final kind in ['reviews', 'versions', 'disclosures'])
              DropdownMenuItem(
                value: kind,
                child: Text(
                  l(switch (kind) {
                    'versions' => 'Plan versions',
                    'disclosures' => 'Teacher disclosures',
                    _ => 'Reviews',
                  }),
                ),
              ),
          ],
          onChanged: _editable
              ? (kind) {
                  if (kind != null) {
                    setState(() {
                      _history = kind;
                      _historyPage = 1;
                    });
                  }
                  _load();
                }
              : null,
        ),
        for (final row in _reviews?.items ?? <SupportReview>[])
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${row.reviewedOn} · ${row.authorName}'),
                  Text(
                    l(
                      row.kind == SupportReviewKindEnum.closure
                          ? 'Closure rationale'
                          : 'Review outcome',
                    ),
                  ),
                  Text(row.outcome),
                  if (row.nextReviewOn != null)
                    Text('${l('Next review date')}: ${row.nextReviewOn}'),
                ],
              ),
            ),
          ),
        for (final row in _versions?.items ?? <SupportVersion>[])
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${l('Revision')} ${row.revision} · ${row.recordedAt}'),
                  Text(
                    '${l('SIAS stage')}: ${row.snapshot.contents.siasStage}',
                  ),
                  _heading('Support needs'),
                  Text(row.snapshot.contents.needs),
                  _heading('Accommodations'),
                  Text(row.snapshot.contents.accommodations),
                ],
              ),
            ),
          ),
        for (final row in _disclosures?.items ?? <SupportDisclosureHistory>[])
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${row.teacherName} · ${row.expiresOn} · ${l(row.active ? 'Active' : 'Withdrawn')}',
                  ),
                  Text(row.accommodations),
                  if (row.active && _access!.canWrite)
                    OutlinedButton(
                      onPressed: _writable
                          ? () => setState(() {
                              _withdrawId = row.id;
                              _changeReason.clear();
                            })
                          : null,
                      child: Text(l('Withdraw instructions')),
                    ),
                  if (row.active && _withdrawId == row.id) ...[
                    _field(_changeReason, 'Reason for this change'),
                    FilledButton(
                      onPressed:
                          _writable && _changeReason.text.trim().length >= 3
                          ? () => _start('revoke-disclosure', row.id, {
                              'revision': row.revision,
                              'reason': _changeReason.text,
                            })
                          : null,
                      child: Text(l('Confirm withdrawal')),
                    ),
                  ],
                ],
              ),
            ),
          ),
        _pageButtons(page, last, (p) {
          setState(() => _historyPage = p);
          _load();
        }),
      ],
    );
  }

  Widget _professionalView() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _heading('My support cases'),
      if (_cases!.items.isEmpty)
        Text(l('No support plans in your current caseload.')),
      for (final row in _cases!.items)
        Card(
          child: ListTile(
            title: Text(row.learnerName),
            subtitle: Text(
              '${l(row.state == SupportCaseSummaryStateEnum.active
                  ? 'Active'
                  : row.state == SupportCaseSummaryStateEnum.closed
                  ? 'Closed'
                  : 'Status unavailable')} ${row.reviewDueOn ?? ''}',
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: _editable ? () => _openPlan(row.id) : null,
          ),
        ),
      if (_record != null)
        OutlinedButton(
          onPressed: _editable ? () => _openPlan(null) : null,
          child: Text(l('Start a support plan')),
        ),
      if (_record case final record?) _currentPlan(record),
      if (_access!.canWrite &&
          (_record == null || _record!.state == SupportPlanStateEnum.active))
        _editPlan(),
      if (_record case final record?
          when _access!.canWrite &&
              record.state == SupportPlanStateEnum.active) ...[
        _reviewForm(record),
        _disclosureForm(record),
        _heading('Close support plan'),
        _field(_changeReason, 'Closure rationale'),
        Text(
          l(
            'Closing withdraws teacher instructions and retains the support history.',
          ),
        ),
        OutlinedButton(
          onPressed: _writable && _changeReason.text.trim().length >= 3
              ? () => _start('close', record.id, {
                  'revision': _baseRevision ?? record.revision,
                  'reason': _changeReason.text,
                })
              : null,
          child: Text(l('Close plan')),
        ),
      ],
      if (_record case final record?) _historyView(record),
    ],
  );
  @override
  Widget build(BuildContext context) {
    final listingLast =
        _cases?.lastPage ??
        _assignments?.lastPage ??
        _instructions?.lastPage ??
        1;
    final last = math.max(
      listingLast,
      math.max(
        _configuration?.lastPage ?? 1,
        _configuration?.staffLastPage ?? 1,
      ),
    );
    return ScreenScaffold(
      title: l('Support plans'),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 1100),
          child: ListView(
            controller: _scroll,
            padding: const EdgeInsets.all(16),
            children: [
              _notice(),
              if (!_hidden && _access != null) ...[
                Text(
                  l(
                    'Private support records are limited to assigned professionals. Teacher instructions contain only approved accommodations.',
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  key: ValueKey('mode-$_audience'),
                  initialValue: _audience,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: l('Support view')),
                  items: [
                    for (final mode in _access!.audiences.where(
                      ['professional', 'principal', 'teacher'].contains,
                    ))
                      DropdownMenuItem(
                        value: mode,
                        child: Text(l(_modeName(mode))),
                      ),
                  ],
                  onChanged: _editable ? _selectMode : null,
                ),
                if (_audience != 'teacher' && !_access!.canWrite)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      l(
                        'You can read these records. Changes are currently unavailable.',
                      ),
                    ),
                  ),
                if (_audience != 'teacher') ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: TextField(
                      controller: _search,
                      readOnly: !_editable,
                      decoration: InputDecoration(
                        labelText: l('Find learner or staff member'),
                        suffixIcon: IconButton(
                          tooltip: l('Search'),
                          onPressed: _editable
                              ? () {
                                  setState(() => _page = 1);
                                  _load();
                                }
                              : null,
                          icon: const Icon(Icons.search),
                        ),
                      ),
                      onSubmitted: _editable
                          ? (_) {
                              setState(() => _page = 1);
                              _load();
                            }
                          : null,
                      maxLength: 100,
                    ),
                  ),
                  Text(l('Changing learners clears the unsaved draft.')),
                ],
                if (_audience == 'teacher')
                  _teacherView()
                else if (_audience == 'principal')
                  _principalView()
                else
                  _professionalView(),
                _pageButtons(_page, last, (p) {
                  setState(() => _page = p);
                  _load();
                }),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
