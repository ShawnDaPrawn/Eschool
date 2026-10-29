import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'diary_dates.dart';
import 'diary_repository.dart';

/// What the editor did, for the diary to show at once.
sealed class DiaryEditResult {
  const DiaryEditResult();
}

class DiarySaved extends DiaryEditResult {
  const DiarySaved(this.entry);
  final DiaryEntry entry;
}

class DiaryDeletedEntry extends DiaryEditResult {
  const DiaryDeletedEntry(this.id);
  final int id;
}

/// Writes or changes one private note or to-do. The text stays in the form
/// until the server has it, so a failed save loses nothing.
class DiaryEntryEditor extends StatefulWidget {
  const DiaryEntryEditor({
    required this.repository,
    required this.day,
    this.entry,
    this.todo = true,
    super.key,
  });
  final DiaryRepository repository;

  /// The day the learner was looking at, used for a new entry.
  final DateTime day;
  final DiaryEntry? entry;

  /// For a new entry: a to-do or a note.
  final bool todo;

  @override
  State<DiaryEntryEditor> createState() => _DiaryEntryEditorState();
}

class _DiaryEntryEditorState extends State<DiaryEntryEditor> {
  late final TextEditingController _title, _body;
  // One operation id per new entry: a retried save cannot make a copy.
  final String _operation = const Uuid().v4();
  // Once a create has been sent the entry may exist, so its kind is fixed.
  bool _sent = false;
  // The newest copy of the entry the server is known to hold. A save whose
  // reply was lost is found again from it, so a retry never sends a stale
  // revision.
  late DiaryEntry? _known = widget.entry;
  final Set<String> _recoveryDates = {};
  late bool _todo, _done;
  DateTime? _date, _remind;
  bool _busy = false;
  String? _error;
  String l(String key) => academicText(context, key);

  @override
  void initState() {
    super.initState();
    final entry = widget.entry;
    _title = TextEditingController(text: entry?.title ?? '');
    _body = TextEditingController(text: entry?.body ?? '');
    _todo = entry == null ? widget.todo : entry.kind == DiaryEntryKindEnum.todo;
    _done = entry?.done ?? false;
    _date = entry == null
        ? diaryDate(widget.day)
        : (entry.date == null ? null : diaryDate(entry.date!));
    _remind = diaryParse(entry?.remindAt);
  }

  @override
  void dispose() {
    _title.dispose();
    _body.dispose();
    super.dispose();
  }

  DiaryDraft get _draft => DiaryDraft(
    todo: _todo,
    title: _title.text,
    body: _body.text,
    date: _date == null ? null : diaryDay(_date!),
    remindAt: _remind == null ? null : diaryTime(_remind!),
    done: _done,
  );

  String _message(Object error) => error is ArgumentError
      ? l('${error.message}')
      : error is ApiException && error.isApiMessage
      ? error.errorMessage
      : l(
          'Your diary could not be saved. Check your connection and try again.',
        );

  Future<void> _save() async {
    if (_busy) return;
    final draft = _draft;
    if (draft.date != null) _recoveryDates.add(draft.date!);
    final problem = draft.problem ?? _reminderProblem();
    if (problem != null) {
      setState(() => _error = problem);
      return;
    }
    if (!widget.repository.isActive) {
      setState(
        () => _error =
            'Your diary is checking your access. Try again in a moment.',
      );
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      var saved = _known;
      if (saved == null) {
        _sent = true;
        // A retry after a lost reply returns the entry the first try made.
        saved = _known = await widget.repository.create(_operation, draft);
      }
      // Anything the learner changed since is saved onto it.
      if (!diaryEntryMatches(saved, draft)) {
        saved = _known = await widget.repository.update(
          saved.id,
          saved.revision,
          draft,
        );
      }
      if (mounted) Navigator.of(context).pop(DiarySaved(saved));
    } catch (error) {
      // The save may have reached the server, or another device may have
      // changed the entry: read its newest copy before the learner retries.
      final current = await _refetch();
      if (!mounted) return;
      if (current != null && diaryEntryMatches(current, draft)) {
        Navigator.of(context).pop(DiarySaved(current));
        return;
      }
      setState(() {
        _busy = false;
        _error = _message(error);
      });
    }
  }

  /// The server's newest copy of the entry being edited, or null when it
  /// cannot be read now (or no entry exists yet).
  Future<DiaryEntry?> _refetch() async {
    final known = _known;
    if (known == null || !widget.repository.isActive) return null;
    final days = <String>{
      ..._recoveryDates,
      if (known.date != null) diaryDay(diaryDate(known.date!)),
      if (known.doneOn != null) diaryDay(diaryDate(known.doneOn!)),
    };
    final now = diaryDate(DateTime.now());
    final ranges = [
      for (final day in days) (day, day),
      // Undated completions use the school's day, which can differ from
      // the device's day. Open undated entries appear in every range.
      (diaryDay(diaryAddDays(now, -2)), diaryDay(diaryAddDays(now, 2))),
    ];
    for (final (from, to) in ranges) {
      try {
        final range = await widget.repository.range(from, to);
        for (final entry in range.entries) {
          if (entry.id == known.id) return _known = entry;
        }
      } catch (_) {
        // Still unreachable: keep the draft for the next Save.
        return null;
      }
    }
    return null;
  }

  /// A newly chosen reminder must still be to come; an unchanged old one
  /// is kept as it is.
  String? _reminderProblem() {
    final remind = _remind;
    if (remind == null) return null;
    final unchanged = widget.entry?.remindAt == diaryTime(remind);
    return unchanged || remind.isAfter(DateTime.now())
        ? null
        : 'Choose a reminder time that is still to come.';
  }

  Future<void> _delete() async {
    final entry = widget.entry;
    if (entry == null || _busy) return;
    final sure = await showDialog<bool>(
      context: context,
      // In the diary's own navigator, so closing the diary closes this too.
      useRootNavigator: false,
      builder: (context) => AlertDialog(
        title: AcademicText(
          entry.kind == DiaryEntryKindEnum.todo
              ? 'Delete this to-do?'
              : 'Delete this note?',
        ),
        content: const AcademicText(
          'It is deleted for good and cannot be brought back.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const AcademicText('Keep it'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const AcademicText('Delete'),
          ),
        ],
      ),
    );
    if (sure != true || !mounted) return;
    if (!widget.repository.isActive) {
      setState(
        () => _error =
            'Your diary is checking your access. Try again in a moment.',
      );
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.delete(entry.id);
      if (mounted) Navigator.of(context).pop(DiaryDeletedEntry(entry.id));
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = _message(error);
      });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final chosen = await showDatePicker(
      context: context,
      initialDate: _date ?? diaryDate(widget.day),
      firstDate: DateTime(now.year - 2),
      lastDate: DateTime(now.year + 3, 12, 31),
    );
    if (chosen != null && mounted) setState(() => _date = diaryDate(chosen));
  }

  Future<void> _pickReminder() async {
    final start = _remind ?? _date ?? diaryDate(widget.day);
    final now = DateTime.now();
    final day = await showDatePicker(
      context: context,
      initialDate: start.isBefore(diaryDate(now)) ? diaryDate(now) : start,
      firstDate: diaryDate(now),
      lastDate: DateTime(now.year + 3, 12, 31),
    );
    if (day == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: _remind == null
          ? const TimeOfDay(hour: 17, minute: 0)
          : TimeOfDay.fromDateTime(_remind!),
    );
    if (time == null || !mounted) return;
    setState(
      () => _remind = DateTime(
        day.year,
        day.month,
        day.day,
        time.hour,
        time.minute,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final entry = widget.entry;
    final textTheme = Theme.of(context).textTheme;
    return ScreenScaffold(
      title: l(
        entry == null
            ? (_todo ? 'New to-do' : 'New note')
            : (_todo ? 'Edit to-do' : 'Edit note'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (entry == null) ...[
            SegmentedButton<bool>(
              segments: [
                ButtonSegment(
                  value: true,
                  icon: const Icon(Icons.check_box_outlined),
                  label: Text(l('To-do')),
                ),
                ButtonSegment(
                  value: false,
                  icon: const Icon(Icons.sticky_note_2_outlined),
                  label: Text(l('Note')),
                ),
              ],
              selected: {_todo},
              onSelectionChanged: _busy || _sent
                  ? null
                  : (value) => setState(() {
                      _todo = value.first;
                      // A note always belongs to a day.
                      if (!_todo) _date ??= diaryDate(widget.day);
                    }),
            ),
            const SizedBox(height: 16),
          ],
          TextField(
            key: const ValueKey('diary-title'),
            controller: _title,
            enabled: !_busy,
            maxLength: DiaryDraft.maxTitle,
            textCapitalization: TextCapitalization.sentences,
            decoration: InputDecoration(
              labelText: l(_todo ? 'What do you need to do?' : 'Title'),
            ),
          ),
          const SizedBox(height: 8),
          TextField(
            key: const ValueKey('diary-body'),
            controller: _body,
            enabled: !_busy,
            minLines: 4,
            maxLines: 12,
            maxLength: DiaryDraft.maxBody,
            textCapitalization: TextCapitalization.sentences,
            keyboardType: TextInputType.multiline,
            decoration: InputDecoration(
              labelText: l(_todo ? 'Details (optional)' : 'Your thoughts'),
              alignLabelWithHint: true,
            ),
          ),
          const SizedBox(height: 8),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.event_outlined),
            title: Text(
              _date == null ? l('No date') : diaryDayLabel(context, _date!),
            ),
            subtitle: _todo && _date == null
                ? Text(l('Shows on today\'s page until it is done.'))
                : null,
            onTap: _busy ? null : _pickDate,
            trailing: _todo && _date != null
                ? IconButton(
                    tooltip: l('Remove the date'),
                    icon: const Icon(Icons.close),
                    onPressed: _busy
                        ? null
                        : () => setState(() => _date = null),
                  )
                : null,
          ),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.alarm_outlined),
            title: Text(
              _remind == null
                  ? l('Remind me')
                  : '${diaryDayLabel(context, _remind!)}, ${diaryClock(context, _remind!)}',
            ),
            onTap: _busy ? null : _pickReminder,
            trailing: _remind != null
                ? IconButton(
                    tooltip: l('Remove the reminder'),
                    icon: const Icon(Icons.close),
                    onPressed: _busy
                        ? null
                        : () => setState(() => _remind = null),
                  )
                : null,
          ),
          if (_todo && entry != null)
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _done,
              onChanged: _busy
                  ? null
                  : (value) => setState(() => _done = value ?? false),
              title: Text(l('Done')),
              controlAffinity: ListTileControlAffinity.leading,
            ),
          const SizedBox(height: 8),
          Row(
            children: [
              Icon(
                Icons.lock_outline,
                size: 16,
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  l('Only you can see this.'),
                  style: textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ],
          ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Text(
                l(_error!),
                key: const ValueKey('diary-editor-error'),
                style: textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.error,
                ),
              ),
            ),
          const SizedBox(height: 16),
          FilledButton(
            key: const ValueKey('diary-save'),
            onPressed: _busy ? null : _save,
            child: _busy
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(l('Save')),
          ),
          if (entry != null) ...[
            const SizedBox(height: 8),
            TextButton.icon(
              key: const ValueKey('diary-delete'),
              onPressed: _busy ? null : _delete,
              icon: const Icon(Icons.delete_outline),
              label: Text(l('Delete')),
              style: TextButton.styleFrom(
                foregroundColor: Theme.of(context).colorScheme.error,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
