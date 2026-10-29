import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import 'diary_dates.dart';
import 'diary_repository.dart';

/// The learner's own layer on a school item: ticked off, a reminder and a
/// private note. The school sees none of it.
class DiaryItemSheet extends StatefulWidget {
  const DiaryItemSheet({
    required this.repository,
    required this.item,
    super.key,
  });
  final DiaryRepository repository;
  final DiarySchoolItem item;

  @override
  State<DiaryItemSheet> createState() => _DiaryItemSheetState();
}

class _DiaryItemSheetState extends State<DiaryItemSheet> {
  late final TextEditingController _note;
  late bool _done;
  DateTime? _remind;
  bool _busy = false;
  String? _error;
  String l(String key) => academicText(context, key);

  bool get _submitted => widget.item.homework?.submitted == true;

  @override
  void initState() {
    super.initState();
    _note = TextEditingController(text: widget.item.state.note ?? '');
    _done = widget.item.state.done;
    _remind = diaryParse(widget.item.state.remindAt);
  }

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _pickReminder() async {
    final now = DateTime.now();
    final start = _remind ?? diaryDate(widget.item.date);
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

  Future<void> _save() async {
    if (_busy || !widget.repository.isActive) return;
    if (_note.text.length > 2000) {
      setState(() => _error = 'Keep the note to 2,000 characters.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final state = await widget.repository.setItemState(
        widget.item,
        done: _done,
        remindAt: _remind == null ? null : diaryTime(_remind!),
        note: _note.text,
      );
      if (mounted) Navigator.of(context).pop(state);
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'Your diary could not be saved. Check your connection and try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final item = widget.item;
    final textTheme = Theme.of(context).textTheme;
    final colors = Theme.of(context).colorScheme;
    final due = diaryParse(item.dueAt);
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: 16 + MediaQuery.viewInsetsOf(context).bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(item.title, style: textTheme.titleLarge),
            if (item.subject != null)
              Text(item.subject!.name, style: textTheme.bodyMedium),
            if (due != null)
              Text(
                '${l(item.source_ == DiaryItemSource.homework ? 'Due' : 'Starts')}: '
                '${diaryDayLabel(context, due)}, ${diaryClock(context, due)}',
                style: textTheme.bodyMedium,
              ),
            if (item.endDate != null)
              Text(
                '${l('Until')} ${diaryDayLabel(context, item.endDate!)}',
                style: textTheme.bodyMedium,
              ),
            const SizedBox(height: 8),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _submitted || _done,
              onChanged: _busy || _submitted
                  ? null
                  : (value) => setState(() => _done = value ?? false),
              title: Text(l(_submitted ? 'Handed in' : 'Done')),
              controlAffinity: ListTileControlAffinity.leading,
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
            TextField(
              key: const ValueKey('diary-item-note'),
              controller: _note,
              enabled: !_busy,
              minLines: 2,
              maxLines: 6,
              maxLength: 2000,
              textCapitalization: TextCapitalization.sentences,
              decoration: InputDecoration(
                labelText: l('My private note'),
                helperText: l('Only you can see this, not your teacher.'),
                alignLabelWithHint: true,
              ),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  l(_error!),
                  style: textTheme.bodyMedium?.copyWith(color: colors.error),
                ),
              ),
            const SizedBox(height: 12),
            FilledButton(
              key: const ValueKey('diary-item-save'),
              onPressed: _busy ? null : _save,
              child: _busy
                  ? const SizedBox.square(
                      dimension: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(l('Save')),
            ),
          ],
        ),
      ),
    );
  }
}
