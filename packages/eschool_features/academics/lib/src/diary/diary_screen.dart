import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import 'diary_dates.dart';
import 'diary_entry_editor.dart';
import 'diary_item_sheet.dart';
import 'diary_reminders.dart';
import 'diary_repository.dart';

/// The learner's diary: a page per day with the school's homework, events
/// and holidays placed on it automatically, and the learner's own private
/// to-dos and notes. Only the learner can read what they write.
class DiaryScreen extends StatefulWidget {
  const DiaryScreen({
    required this.repository,
    this.canWrite,
    this.onTalkToSomeone,
    this.reminders,
    this.onBack,
    this.now,
    super.key,
  });
  final DiaryRepository repository;

  /// Whether the school currently lets the learner write (the module is not
  /// read-only). Reading still works when it is not.
  final bool Function()? canWrite;

  /// Opens the school's wellbeing reporting, which the learner chooses to use.
  final Future<void> Function()? onTalkToSomeone;
  final DiaryPhoneReminders? reminders;

  /// Leaves the diary (it opens in a navigator of its own).
  final VoidCallback? onBack;

  /// The device clock, replaceable in tests.
  final DateTime Function()? now;

  @override
  State<DiaryScreen> createState() => _DiaryScreenState();
}

class _DiaryScreenState extends State<DiaryScreen> with WidgetsBindingObserver {
  List<DiaryEntry> _entries = const [];
  List<DiarySchoolItem> _items = const [];
  DateTime? _from, _to, _today, _day;
  int _epoch = 0;
  bool _busy = true, _hidden = false, _loaded = false;
  String? _error, _notice;
  // Entries and items with a write on its way, so a quick second tap waits.
  final Set<String> _saving = {};
  Future<void> _reminderSync = Future<void>.value();

  bool get _active => mounted && !_hidden && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  bool get _writable => _active && _loaded && (widget.canWrite?.call() ?? true);
  DateTime get _now => widget.now?.call() ?? DateTime.now();
  String l(String key) => academicText(context, key);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load(diaryDate(_now));
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Private text is cleared as soon as the app leaves the front (from
  /// `inactive`, while frames still draw), so the app switcher and a
  /// borrowed phone show nothing. It is loaded again on return.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      if (!_hidden) return;
      _hidden = false;
      _load(_day ?? diaryDate(_now));
    } else if (!_hidden) {
      _epoch++;
      _hidden = true;
      if (mounted) {
        setState(() {
          _entries = const [];
          _items = const [];
          _loaded = false;
          _busy = true;
        });
      }
    }
  }

  /// Loads the week around [day]. [quiet] keeps the page on screen meanwhile.
  Future<void> _load(DateTime day, {bool quiet = false}) async {
    final epoch = ++_epoch;
    if (!_active) {
      // Rebuild, so a withdrawn session stops showing the diary at once.
      if (mounted) setState(() {});
      return;
    }
    final from = diaryWeekStart(day), to = diaryAddDays(from, 6);
    setState(() {
      _busy = !quiet;
      _error = null;
      if (!quiet) _notice = null;
    });
    try {
      final range = await widget.repository.range(diaryDay(from), diaryDay(to));
      if (!_current(epoch)) return;
      final today = diaryDate(range.today);
      setState(() {
        _entries = range.entries;
        _items = range.schoolItems;
        _from = diaryDate(range.from);
        _to = diaryDate(range.to);
        _today = today;
        _day = _loaded ? diaryDate(day) : (_day ?? today);
        _loaded = true;
        _busy = false;
      });
      _syncReminders();
      // The school's today can fall in another week than the device's.
      final shown = _day!;
      if (shown.isBefore(_from!) || shown.isAfter(_to!)) {
        await _load(shown);
      }
    } catch (error) {
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
        _error = error is ApiException && error.statusCode == 403
            ? 'Reopen your diary to check your access.'
            : 'Your diary could not be opened. Check your connection and try again.';
      });
    }
  }

  /// The phone's diary reminders become the server's complete list of
  /// upcoming ones, so moved, done, deleted and unviewed entries are all
  /// right. One sync runs at a time; each asks the server afresh.
  void _syncReminders() {
    final phone = widget.reminders;
    if (phone == null) return;
    final repository = widget.repository;
    _reminderSync = _reminderSync.then((_) async {
      if (!mounted || !repository.isActive) return;
      try {
        final upcoming = await repository.reminders();
        if (!mounted || !repository.isActive) return;
        await phone.replaceAll(
          repository.schoolId,
          repository.readerId,
          upcoming.reminders,
        );
      } catch (_) {
        // Reminders are a convenience; the next sync tries again.
      }
    });
  }

  void _show(DateTime day) {
    final date = diaryDate(day);
    if (_from != null &&
        _to != null &&
        !date.isBefore(_from!) &&
        !date.isAfter(_to!)) {
      setState(() => _day = date);
    } else {
      _day = date;
      _load(date);
    }
  }

  bool _onDay(DiarySchoolItem item, DateTime day) {
    final start = diaryDate(item.date);
    final end = item.endDate == null ? start : diaryDate(item.endDate!);
    return !day.isBefore(start) && !day.isAfter(end);
  }

  /// A dated entry shows on its day; an undated to-do that is done shows on
  /// the day it was done (open ones are on today's page, "Anytime").
  bool _entryOn(DiaryEntry entry, DateTime day) => entry.date != null
      ? diaryDate(entry.date!) == day
      : entry.done && entry.doneOn != null && diaryDate(entry.doneOn!) == day;

  bool _hasSomething(DateTime day) =>
      _items.any((item) => _onDay(item, day)) ||
      _entries.any((entry) => _entryOn(entry, day));

  Future<void> _readEntry(DiaryEntry entry) async {
    if (!_active || !_loaded) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (detailContext) => ScreenScaffold(
          title: l('My diary'),
          onBack: () => Navigator.of(detailContext).maybePop(),
          body: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: SelectionArea(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    entry.title,
                    style: Theme.of(detailContext).textTheme.titleLarge,
                  ),
                  if (entry.body != null) ...[
                    const SizedBox(height: 16),
                    Text(entry.body!),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _edit({DiaryEntry? entry, bool todo = true}) async {
    if (!_writable) return;
    final result = await Navigator.of(context).push<DiaryEditResult>(
      MaterialPageRoute(
        builder: (_) => DiaryEntryEditor(
          repository: widget.repository,
          day: _day ?? diaryDate(_now),
          entry: entry,
          todo: todo,
        ),
      ),
    );
    if (!mounted || result == null) return;
    setState(
      () => _notice = switch (result) {
        DiarySaved() => 'Saved.',
        DiaryDeletedEntry() => 'Deleted.',
      },
    );
    await _load(_day ?? diaryDate(_now), quiet: true);
  }

  Future<void> _toggleTodo(DiaryEntry entry, bool done) async {
    final key = 'entry:${entry.id}';
    if (!_writable || _saving.contains(key)) return;
    final epoch = _epoch;
    setState(() => _saving.add(key));
    try {
      await widget.repository.update(
        entry.id,
        entry.revision,
        DiaryDraft(
          todo: true,
          title: entry.title,
          body: entry.body,
          date: entry.date == null ? null : diaryDay(entry.date!),
          remindAt: entry.remindAt,
          done: done,
        ),
      );
    } catch (error) {
      if (_current(epoch)) {
        setState(
          () => _notice = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Your diary could not be saved. Check your connection and try again.',
        );
      }
    }
    if (_current(epoch)) await _load(_day ?? diaryDate(_now), quiet: true);
    if (mounted) setState(() => _saving.remove(key));
  }

  Future<void> _toggleItem(DiarySchoolItem item, bool done) async {
    final key = diaryItemKey(item);
    if (!_writable || _saving.contains(key)) return;
    final epoch = _epoch;
    setState(() => _saving.add(key));
    try {
      await widget.repository.setItemState(
        item,
        done: done,
        remindAt: item.state.remindAt,
        note: item.state.note,
      );
    } catch (_) {
      if (_current(epoch)) {
        setState(
          () => _notice =
              'Your diary could not be saved. Check your connection and try again.',
        );
      }
    }
    if (_current(epoch)) await _load(_day ?? diaryDate(_now), quiet: true);
    if (mounted) setState(() => _saving.remove(key));
  }

  Future<void> _openItem(DiarySchoolItem item) async {
    if (!_writable) return;
    final saved = await showModalBottomSheet<DiaryItemState>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => DiaryItemSheet(repository: widget.repository, item: item),
    );
    if (!mounted || saved == null) return;
    setState(() => _notice = 'Saved.');
    await _load(_day ?? diaryDate(_now), quiet: true);
  }

  Widget _heading(String title, {String? subtitle}) => Padding(
    padding: const EdgeInsets.only(top: 20, bottom: 4),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l(title), style: Theme.of(context).textTheme.titleMedium),
        if (subtitle != null)
          Text(
            l(subtitle),
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
      ],
    ),
  );

  Widget _weekStrip() {
    final from = _from!;
    final colors = Theme.of(context).colorScheme;
    return Row(
      children: [
        IconButton(
          tooltip: l('Previous week'),
          icon: const Icon(Icons.chevron_left),
          onPressed: () => _show(diaryAddDays(from, -7)),
        ),
        Expanded(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              for (var i = 0; i < 7; i++)
                Builder(
                  builder: (context) {
                    final day = diaryAddDays(from, i);
                    final selected = day == _day;
                    final today = day == _today;
                    return Expanded(
                      child: Tooltip(
                        message: diaryDayLabel(context, day),
                        child: TextButton(
                          key: ValueKey('diary-day-${diaryDay(day)}'),
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            minimumSize: const Size(0, 52),
                            backgroundColor: selected ? colors.primary : null,
                            foregroundColor: selected
                                ? colors.onPrimary
                                : (today ? colors.primary : colors.onSurface),
                          ),
                          onPressed: () => setState(() => _day = day),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(diaryWeekday(context, day)),
                              Text(
                                '${day.day}',
                                style: TextStyle(
                                  fontWeight: today || selected
                                      ? FontWeight.bold
                                      : FontWeight.normal,
                                ),
                              ),
                              Icon(
                                Icons.circle,
                                size: 5,
                                color: _hasSomething(day)
                                    ? (selected
                                          ? colors.onPrimary
                                          : colors.primary)
                                    : Colors.transparent,
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
            ],
          ),
        ),
        IconButton(
          tooltip: l('Next week'),
          icon: const Icon(Icons.chevron_right),
          onPressed: () => _show(diaryAddDays(from, 7)),
        ),
      ],
    );
  }

  String? _homeworkStatus(DiarySchoolItem item) {
    final homework = item.homework;
    if (homework == null) return null;
    if (homework.status == DiaryHomeworkStatusEnum.accepted) {
      return homework.points != null && homework.total != null
          ? '${l('Marked')}: ${homework.points}/${homework.total}'
          : l('Marked');
    }
    if (homework.status == DiaryHomeworkStatusEnum.rejected) {
      return l('Returned: hand it in again');
    }
    if (homework.submitted) return l('Handed in');
    if (homework.overdue) return l('Late: not handed in yet');
    return null;
  }

  Widget _itemTile(DiarySchoolItem item) {
    final colors = Theme.of(context).colorScheme;
    final status = StatusColors.of(context);
    final homework = item.homework;
    final due = diaryParse(item.dueAt);
    final late = homework?.overdue == true;
    final submitted = homework?.submitted == true;
    final lines = <String>[
      if (item.subject != null) item.subject!.name,
      if (late && due != null)
        '${l('Was due')} ${diaryShortDay(context, due)}, ${diaryClock(context, due)}'
      else if (due != null)
        '${l(item.source_ == DiaryItemSource.homework ? 'Due' : 'Starts')} ${diaryClock(context, due)}',
      if (item.endDate != null)
        '${l('Until')} ${diaryShortDay(context, item.endDate!)}',
      ?_homeworkStatus(item),
      if ((item.state.note ?? '').isNotEmpty)
        '${l('My note')}: ${item.state.note}',
    ];
    final IconData icon = switch (item.source_) {
      DiaryItemSource.homework => Icons.menu_book_outlined,
      DiaryItemSource.holiday => Icons.beach_access_outlined,
      _ => Icons.event_outlined,
    };
    return Card(
      key: ValueKey('diary-item-${diaryItemKey(item)}'),
      child: ListTile(
        leading: item.source_ == DiaryItemSource.holiday
            ? Icon(icon)
            : Checkbox(
                value: item.state.done,
                onChanged:
                    !_writable ||
                        submitted ||
                        _saving.contains(diaryItemKey(item))
                    ? null
                    : (value) => _toggleItem(item, value ?? false),
              ),
        title: Text(
          item.title,
          style: item.state.done
              ? TextStyle(
                  decoration: TextDecoration.lineThrough,
                  color: colors.onSurfaceVariant,
                )
              : null,
        ),
        subtitle: lines.isEmpty
            ? null
            : Text(
                lines.join('\n'),
                style: late ? TextStyle(color: status.danger) : null,
              ),
        trailing: Icon(icon, color: colors.onSurfaceVariant),
        onTap: _writable ? () => _openItem(item) : null,
      ),
    );
  }

  Widget _entryTile(DiaryEntry entry) {
    final colors = Theme.of(context).colorScheme;
    final todo = entry.kind == DiaryEntryKindEnum.todo;
    final remind = diaryParse(entry.remindAt);
    final body = entry.body?.trim();
    final lines = <String>[
      if (body != null && body.isNotEmpty) body,
      if (remind != null)
        '${l('Reminder')}: ${diaryShortDay(context, remind)}, ${diaryClock(context, remind)}',
    ];
    return Card(
      key: ValueKey('diary-entry-${entry.id}'),
      child: ListTile(
        leading: todo
            ? Checkbox(
                value: entry.done,
                onChanged: _writable && !_saving.contains('entry:${entry.id}')
                    ? (value) => _toggleTodo(entry, value ?? false)
                    : null,
              )
            : const Icon(Icons.sticky_note_2_outlined),
        title: Text(
          entry.title,
          style: entry.done
              ? TextStyle(
                  decoration: TextDecoration.lineThrough,
                  color: colors.onSurfaceVariant,
                )
              : null,
        ),
        subtitle: lines.isEmpty
            ? null
            : Text(
                lines.join('\n'),
                maxLines: todo ? 3 : 6,
                overflow: TextOverflow.ellipsis,
              ),
        onTap: _active && _loaded
            ? () => _writable ? _edit(entry: entry) : _readEntry(entry)
            : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final day = _day;
    if (_hidden) {
      // Away from the front: nothing private, and nothing animating.
      return ScreenScaffold(
        title: l('My diary'),
        onBack: widget.onBack,
        body: Center(
          child: Icon(
            Icons.lock_outline,
            size: 48,
            color: colors.onSurfaceVariant,
          ),
        ),
      );
    }
    if (!widget.repository.isActive) {
      return ScreenScaffold(
        title: l('My diary'),
        onBack: widget.onBack,
        body: Center(
          child: Text(
            l('Reopen your diary to check your access.'),
            key: const ValueKey('diary-closed'),
            textAlign: TextAlign.center,
          ),
        ),
      );
    }
    if ((_busy && !_loaded) || day == null) {
      return ScreenScaffold(
        title: l('My diary'),
        onBack: widget.onBack,
        body: Center(
          child: _error != null && !_busy
              ? Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        l(_error!),
                        key: const ValueKey('diary-error'),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: () => _load(_day ?? diaryDate(_now)),
                        icon: const Icon(Icons.refresh),
                        label: Text(l('Try again')),
                      ),
                    ],
                  ),
                )
              : const CircularProgressIndicator(),
        ),
      );
    }
    final isToday = day == _today;
    final items = _items.where((item) => _onDay(item, day)).toList();
    final todos = _entries
        .where(
          (entry) =>
              entry.kind == DiaryEntryKindEnum.todo && _entryOn(entry, day),
        )
        .toList();
    final anytime = isToday
        ? _entries
              .where(
                (entry) =>
                    entry.kind == DiaryEntryKindEnum.todo &&
                    entry.date == null &&
                    !entry.done,
              )
              .toList()
        : const <DiaryEntry>[];
    final notes = _entries
        .where(
          (entry) =>
              entry.kind == DiaryEntryKindEnum.note && _entryOn(entry, day),
        )
        .toList();
    final nothing =
        items.isEmpty && todos.isEmpty && anytime.isEmpty && notes.isEmpty;
    return ScreenScaffold(
      title: l('My diary'),
      onBack: widget.onBack,
      action: IconButton(
        tooltip: l('Refresh'),
        icon: const Icon(Icons.refresh),
        onPressed: () => _load(day),
      ),
      body: RefreshIndicator(
        onRefresh: () => _load(day, quiet: true),
        child: ListView(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
          children: [
            _weekStrip(),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      diaryDayLabel(context, day),
                      key: const ValueKey('diary-day-title'),
                      style: textTheme.titleLarge,
                    ),
                  ),
                  if (!isToday && _today != null)
                    TextButton(
                      onPressed: () => _show(_today!),
                      child: Text(l('Today')),
                    ),
                ],
              ),
            ),
            if (_busy) const LinearProgressIndicator(),
            if (_error != null)
              Text(
                l(_error!),
                key: const ValueKey('diary-error'),
                style: textTheme.bodyMedium?.copyWith(color: colors.error),
              ),
            if (_notice != null)
              Text(
                l(_notice!),
                key: const ValueKey('diary-notice'),
                style: textTheme.bodyMedium,
              ),
            Card(
              color: colors.surfaceContainerLow,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Icon(Icons.lock_outline, color: colors.primary),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        l(
                          'Your notes and to-dos are private. Only you can see them: not your parents, not your teachers.',
                        ),
                        style: textTheme.bodySmall,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (_writable)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    FilledButton.icon(
                      key: const ValueKey('diary-add-todo'),
                      onPressed: () => _edit(todo: true),
                      icon: const Icon(Icons.add_task),
                      label: Text(l('Add a to-do')),
                    ),
                    OutlinedButton.icon(
                      key: const ValueKey('diary-add-note'),
                      onPressed: () => _edit(todo: false),
                      icon: const Icon(Icons.edit_note),
                      label: Text(l('Write a note')),
                    ),
                  ],
                ),
              )
            else if (_loaded)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(l('Your diary is read-only right now.')),
              ),
            if (items.isNotEmpty) ...[
              _heading(
                'From school',
                subtitle: 'Added automatically. Tick them off as you go.',
              ),
              for (final item in items) _itemTile(item),
            ],
            if (todos.isNotEmpty) ...[
              _heading('My to-dos'),
              for (final entry in todos) _entryTile(entry),
            ],
            if (anytime.isNotEmpty) ...[
              _heading('Anytime', subtitle: 'To-dos without a date.'),
              for (final entry in anytime) _entryTile(entry),
            ],
            if (notes.isNotEmpty) ...[
              _heading('My notes'),
              for (final entry in notes) _entryTile(entry),
            ],
            if (nothing)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 32),
                child: Text(
                  l('Nothing for this day yet.'),
                  key: const ValueKey('diary-empty'),
                  textAlign: TextAlign.center,
                  style: textTheme.bodyMedium?.copyWith(
                    color: colors.onSurfaceVariant,
                  ),
                ),
              ),
            if (widget.onTalkToSomeone != null) ...[
              const Divider(height: 32),
              ListTile(
                key: const ValueKey('diary-talk'),
                leading: Icon(
                  Icons.support_agent_outlined,
                  color: colors.primary,
                ),
                title: Text(l('Talk to someone')),
                subtitle: Text(
                  l(
                    'Nobody reads your diary. If something is worrying you, you can tell someone at school.',
                  ),
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: widget.onTalkToSomeone,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
