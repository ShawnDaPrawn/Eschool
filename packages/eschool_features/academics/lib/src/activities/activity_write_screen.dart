import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../content/content_widgets.dart';
import 'activity_repository.dart';

class ActivityWriteScreen extends StatefulWidget {
  const ActivityWriteScreen({
    required this.repository,
    required this.detail,
    required this.mode,
    required this.enabled,
    required this.onDirty,
    required this.onSaved,
    this.record,
    super.key,
  });
  final ActivityRepository repository;
  final ActivityGroupDetail detail;
  final String mode;
  final Map<String, dynamic>? record;
  final bool enabled;
  final ValueChanged<bool> onDirty;
  final Future<void> Function() onSaved;
  @override
  State<ActivityWriteScreen> createState() => _ActivityWriteScreenState();
}

class _ActivityWriteScreenState extends State<ActivityWriteScreen> {
  final _form = GlobalKey<FormState>();
  final _fields = <String, TextEditingController>{};
  final _statuses = <int, String>{};
  ActivityGroupRoster? _roster;
  List<ActivitySessionRosterLearner> _members = [];
  Map<String, dynamic>? _pending, _current;
  ActivityGroupRoster? _currentRoster;
  String _operation = const Uuid().v4(), _status = 'scheduled';
  String? _error;
  late int _revision;
  bool _published = false,
      _busy = false,
      _uncertain = false,
      _conflict = false,
      _blocked = false;
  int get group => widget.detail.group.id;
  String l(String s) => contentLabel(context, s);
  @override
  void initState() {
    super.initState();
    _revision = (widget.record?['revision'] as int?) ?? 0;
    _status = widget.record?['status'] as String? ?? 'scheduled';
    if (widget.mode == 'session' &&
        !['scheduled', 'completed', 'cancelled'].contains(_status)) {
      _blocked = true;
      _error = 'Update the app to edit this activity record.';
    }
    _published = widget.record?['published'] == true;
    for (final key in [
      'name',
      'venue',
      'on_date',
      'title',
      'markdown',
      'reason',
    ]) {
      _fields[key] = TextEditingController(
        text: widget.record?[key]?.toString() ?? '',
      )..addListener(() => widget.onDirty(true));
    }
    if (widget.mode == 'attendance') _loadRoster();
  }

  @override
  void dispose() {
    for (final field in _fields.values) {
      field.dispose();
    }
    super.dispose();
  }

  List<ActivitySessionRosterLearner> _eligible(ActivityGroupRoster roster) {
    final selected = roster.sessionRoster;
    if (selected == null ||
        selected.sessionId != widget.record!['id'] ||
        selected.groupId != group) {
      throw ApiException(
        'Connect to the updated school service to open this register.',
        isApiMessage: true,
      );
    }
    return selected.learners;
  }

  Future<void> _loadRoster() async {
    setState(() => _busy = true);
    try {
      final result = await widget.repository.roster(
        group,
        sessionId: widget.record!['id'] as int,
      );
      if (!mounted) return;
      final members = _eligible(result);
      if (members.any(
        (a) =>
            a.status != null &&
            !['present', 'absent', 'late', 'excused'].contains(a.status),
      )) {
        setState(() {
          _busy = false;
          _blocked = true;
          _error = 'Update the app to edit this activity record.';
        });
        return;
      }
      setState(() {
        _roster = result;
        _members = members;
        _revision = result.sessionRoster!.sessionRevision;
        for (final m in _members) {
          _statuses[m.learnerId] = m.status ?? '';
        }
        _busy = false;
      });
    } catch (e) {
      if (mounted) {
        _failure(e);
        if (!_blocked) {
          setState(() {
            _uncertain = false;
            _error = 'Connect to load the group roster.';
          });
        }
      }
    }
  }

  void _failure(Object error) {
    final code = error is ApiException ? error.statusCode : null;
    setState(() {
      _busy = false;
      _blocked = !widget.repository.isActive || [401, 403, 404].contains(code);
      _conflict = code == 409;
      _uncertain = !_blocked && !_conflict && code != 422;
      _error = _blocked
          ? 'Your activity access changed. Reopen the group.'
          : _conflict
          ? 'This record changed. Review the current saved values.'
          : _uncertain
          ? 'The save result is unknown. Retry the same operation.'
          : 'Check your entries and try again.';
      if (!_uncertain) _pending = null;
      if (_blocked) {
        for (final c in _fields.values) {
          c.clear();
        }
        _statuses.clear();
        _roster = null;
        _members = [];
        widget.onDirty(false);
      }
    });
  }

  Map<String, dynamic> _input() {
    final result = <String, dynamic>{
      'operation_id': _operation,
      'revision': _revision,
    };
    if (widget.mode == 'session') {
      result.addAll({
        'name': _fields['name']!.text.trim(),
        'venue': _fields['venue']!.text.trim(),
        'on_date': _fields['on_date']!.text,
        'status': _status,
      });
    }
    if (widget.mode == 'material') {
      result.addAll({
        'title': _fields['title']!.text.trim(),
        'markdown': _fields['markdown']!.text,
        'published': _published,
      });
    }
    if (widget.mode == 'attendance') {
      result.addAll({
        'reason': _fields['reason']!.text.trim(),
        'entries': [
          for (final m in _members)
            if ((_statuses[m.learnerId] ?? '').isNotEmpty)
              {
                'learner_id': m.learnerId,
                'status': _statuses[m.learnerId],
                'revision': m.revision,
              },
        ],
      });
    }
    return result;
  }

  Future<void> _save() async {
    if (!widget.enabled || _blocked || _busy || _conflict) return;
    if (_pending == null) {
      if (!_form.currentState!.validate()) return;
      if (widget.mode == 'attendance' &&
          _statuses.values.every((s) => s.isEmpty)) {
        setState(() => _error = 'Choose at least one attendance entry.');
        return;
      }
      _pending = _input();
    }
    setState(() => _busy = true);
    try {
      final input = _pending!;
      final id = widget.record?['id'] as int?;
      if (widget.mode == 'session') {
        await widget.repository.session(
          group,
          id,
          ActivitySessionWrite.fromJson(input),
        );
      }
      if (widget.mode == 'material') {
        await widget.repository.material(
          group,
          id,
          ActivityMaterialWrite.fromJson(input),
        );
      }
      if (widget.mode == 'attendance') {
        await widget.repository.attendance(
          id!,
          ActivityAttendanceWrite.fromJson(input),
        );
      }
      if (!mounted) return;
      if (!widget.repository.isActive) {
        throw ApiException('activity_scope_changed', statusCode: 403);
      }
      widget.onDirty(false);
      await widget.onSaved();
    } catch (e) {
      if (mounted) _failure(e);
    }
  }

  Future<void> _review() async {
    setState(() => _busy = true);
    try {
      final detail = await widget.repository.detail(group);
      if (!detail.canCapture) {
        throw ApiException('activity_scope_changed', statusCode: 403);
      }
      Map<String, dynamic>? current;
      final id = widget.record?['id'];
      if (widget.mode == 'material') {
        for (final row in detail.materials) {
          if (row.id == id) current = row.toJson();
        }
      } else {
        for (final row in detail.sessions) {
          if (row.id == id) current = row.toJson();
        }
      }
      if (current == null) {
        throw ApiException('activity_scope_changed', statusCode: 404);
      }
      final roster = widget.mode == 'attendance'
          ? await widget.repository.roster(
              group,
              sessionId: widget.record!['id'] as int,
            )
          : null;
      if (!mounted) return;
      setState(() {
        _current = current;
        _currentRoster = roster;
        _busy = false;
      });
    } catch (e) {
      if (mounted) _failure(e);
    }
  }

  void _useRevision() {
    setState(() {
      _revision = _current!['revision'] as int;
      if (_currentRoster != null) {
        _roster = _currentRoster;
        _members = _eligible(_currentRoster!);
        _revision = _currentRoster!.sessionRoster!.sessionRevision;
        for (final m in _members) {
          _statuses.putIfAbsent(m.learnerId, () => '');
        }
      }
      _operation = const Uuid().v4();
      _pending = null;
      _current = null;
      _currentRoster = null;
      _conflict = false;
      _uncertain = false;
      _error = null;
    });
  }

  List<Widget> _comparison() {
    final current = _current!;
    final labels = widget.mode == 'material'
        ? {
            'title': 'Material title',
            'markdown': 'Material Markdown',
            'published': 'Published to group members',
            'revision': 'Current saved revision',
          }
        : {
            'name': 'Session name',
            'venue': 'Venue',
            'on_date': 'Session date',
            'status': 'Session status',
            'revision': 'Current saved revision',
          };
    return [
      for (final entry in labels.entries)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                l(entry.value),
                style: Theme.of(context).textTheme.labelLarge,
              ),
              SelectableText(
                current[entry.key] is bool
                    ? l(current[entry.key] == true ? 'Yes' : 'No')
                    : current[entry.key].toString(),
              ),
            ],
          ),
        ),
      if (_currentRoster != null)
        for (final row in _currentRoster!.attendance.where(
          (a) => a.activitySessionId == widget.record!['id'],
        ))
          Text(
            '${_members.where((m) => m.learnerId == row.learnerId).firstOrNull?.learnerName ?? l('Former learner')}: ${l(row.status.value)}',
          ),
    ];
  }

  Widget _text(
    String key,
    String label, {
    int max = 191,
    int lines = 1,
    bool date = false,
  }) => TextFormField(
    controller: _fields[key],
    decoration: InputDecoration(
      labelText: l(label),
      hintText: date ? 'YYYY-MM-DD' : null,
    ),
    maxLength: max,
    maxLines: lines,
    enabled: widget.enabled && !_busy && !_uncertain && !_conflict,
    keyboardType: date ? TextInputType.datetime : null,
    validator: (v) {
      if ((v ?? '').trim().isEmpty) return l('Required');
      if (date && !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(v!)) {
        return l('Use YYYY-MM-DD.');
      }
      if (key == 'reason' && (v ?? '').trim().length < 3) {
        return l('Enter at least three characters.');
      }
      return null;
    },
  );
  @override
  Widget build(BuildContext context) {
    if (_blocked) return Center(child: Text(l(_error!)));
    final enabled = widget.enabled && !_busy && !_uncertain && !_conflict;
    return Form(
      key: _form,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            l(
              widget.mode == 'attendance'
                  ? 'Session attendance'
                  : widget.mode == 'material'
                  ? 'Group material'
                  : 'Group session',
            ),
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          if (_error != null)
            Semantics(
              liveRegion: true,
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Text(l(_error!)),
              ),
            ),
          if (_busy) const LinearProgressIndicator(),
          if (widget.mode == 'session') ...[
            _text('name', 'Session name'),
            _text('venue', 'Venue'),
            _text('on_date', 'Session date', max: 10, date: true),
            DropdownButtonFormField<String>(
              initialValue: _status,
              decoration: InputDecoration(labelText: l('Session status')),
              items: [
                for (final s in ['scheduled', 'completed', 'cancelled'])
                  DropdownMenuItem(value: s, child: Text(l(s))),
              ],
              onChanged: enabled
                  ? (v) {
                      setState(() => _status = v!);
                      widget.onDirty(true);
                    }
                  : null,
            ),
          ],
          if (widget.mode == 'material') ...[
            _text('title', 'Material title'),
            _text('markdown', 'Material Markdown', max: 20000, lines: 10),
            SwitchListTile(
              title: Text(l('Published to group members')),
              value: _published,
              onChanged: enabled
                  ? (v) {
                      setState(() => _published = v);
                      widget.onDirty(true);
                    }
                  : null,
            ),
          ],
          if (widget.mode == 'attendance') ...[
            Text('${widget.record!['name']} · ${widget.record!['on_date']}'),
            Text(l('Blank entries remain unrecorded.')),
            Text(l('Attendance opens on the session date.')),
            if (_roster == null && !_busy)
              OutlinedButton(
                onPressed: widget.enabled ? _loadRoster : null,
                child: Text(l('Refresh roster')),
              ),
            for (final member in _members)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: DropdownButtonFormField<String>(
                  key: ValueKey('attendance-${member.learnerId}'),
                  initialValue: _statuses[member.learnerId],
                  decoration: InputDecoration(labelText: member.learnerName),
                  items: [
                    for (final s in [
                      '',
                      'present',
                      'absent',
                      'late',
                      'excused',
                    ])
                      DropdownMenuItem(
                        value: s,
                        child: Text(l(s.isEmpty ? 'Unrecorded' : s)),
                      ),
                  ],
                  onChanged: enabled
                      ? (v) {
                          setState(() => _statuses[member.learnerId] = v!);
                          widget.onDirty(true);
                        }
                      : null,
                ),
              ),
            _text('reason', 'Attendance reason', max: 2000),
          ],
          if (_conflict) ...[
            OutlinedButton(
              onPressed: _busy ? null : _review,
              child: Text(l('Review current saved values')),
            ),
            if (_current != null) ...[
              Text(
                l('Current saved values'),
                style: Theme.of(context).textTheme.titleMedium,
              ),
              ..._comparison(),
              FilledButton(
                onPressed: _useRevision,
                child: Text(l('Keep my draft and use this revision')),
              ),
            ],
          ] else
            FilledButton(
              onPressed:
                  widget.enabled &&
                      !_busy &&
                      (widget.mode != 'attendance' || _roster != null)
                  ? _save
                  : null,
              child: Text(
                l(_uncertain ? 'Retry same operation' : 'Save activity'),
              ),
            ),
        ],
      ),
    );
  }
}
