import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'timetable_cubit.dart';

class TimetableEditor extends StatefulWidget {
  const TimetableEditor({this.lesson, super.key});
  final AcademicTimetableLesson? lesson;
  @override
  State<TimetableEditor> createState() => _TimetableEditorState();
}

class _TimetableEditorState extends State<TimetableEditor> {
  late final Map<String, dynamic> _original = Map.of(
    context.read<TimetableCubit>().state.draft!,
  );
  bool _edited = false;
  TimetableCubit get cubit => context.read<TimetableCubit>();
  void _change(Map<String, dynamic> values) {
    _edited = true;
    cubit.change(values);
  }

  Future<void> _close() async {
    if (cubit.state.busy || cubit.state.loading) return;
    if (_edited && cubit.state.draft != null) {
      final discard = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Discard placement changes?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Keep editing'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Discard'),
            ),
          ],
        ),
      );
      if (discard != true || !mounted) return;
    }
    if (!cubit.isClosed) cubit.cancelEdit();
    Navigator.pop(context);
  }

  Widget _choice(
    String label,
    String field,
    Map<int, String> options,
    TimetableState state, {
    String? historical,
  }) {
    final value = state.draft![field] as int?;
    return DropdownButtonFormField<int>(
      key: ValueKey('$field:$value'),
      initialValue: value,
      isExpanded: true,
      itemHeight: null,
      decoration: InputDecoration(labelText: label),
      items: [
        for (final entry in options.entries)
          DropdownMenuItem(
            value: entry.key,
            child: Text(
              entry.value,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        if (value != null && !options.containsKey(value))
          DropdownMenuItem(
            value: value,
            enabled: false,
            child: Text(
              '${historical ?? label} (unavailable)',
              overflow: TextOverflow.ellipsis,
            ),
          ),
      ],
      onChanged: state.busy || state.draft!['active'] != true
          ? null
          : (value) => _change({field: value}),
    );
  }

  Widget _date(
    String label,
    String field,
    TimetableState state, {
    bool optional = false,
  }) {
    final value = state.draft![field] as String?;
    return TextFormField(
      key: ValueKey('$field:$value'),
      initialValue: value ?? '',
      readOnly: true,
      decoration: InputDecoration(
        labelText: label,
        suffixIcon: optional && value != null
            ? IconButton(
                tooltip: 'Clear specific date',
                onPressed: state.busy ? null : () => _change({field: null}),
                icon: const Icon(Icons.clear),
              )
            : const Icon(Icons.calendar_today_outlined),
      ),
      onTap: state.busy || state.draft!['active'] != true
          ? null
          : () async {
              final initial =
                  DateTime.tryParse(
                    value ?? state.draft!['starts_on'] as String,
                  ) ??
                  DateTime.now();
              final chosen = await showDatePicker(
                context: context,
                initialDate: initial,
                firstDate: DateTime(1900),
                lastDate: DateTime(2100),
              );
              if (chosen != null && mounted) {
                _change({
                  field: chosen.toIso8601String().substring(0, 10),
                  if (field == 'occurs_on') 'weekday': chosen.weekday,
                });
              }
            },
    );
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<TimetableCubit, TimetableState>(
    builder: (context, state) {
      if (state.loading) {
        return const PopScope(
          canPop: false,
          child: AlertDialog(content: LinearProgressIndicator()),
        );
      }
      final draft = state.draft, config = state.configuration;
      if (draft == null || config == null || state.week?.canManage != true) {
        return AlertDialog(
          title: const Text('Timetable not available'),
          content: Text(state.error ?? 'Reload your school context.'),
          actions: [TextButton(onPressed: _close, child: const Text('Close'))],
        );
      }
      final groupMode =
          draft['teaching_group_id'] != null ||
          draft['class_section_id'] == null;
      final enabled = !state.busy && draft['active'] == true;
      return PopScope(
        canPop: !state.busy && !_edited,
        onPopInvokedWithResult: (didPop, _) {
          if (!didPop) _close();
        },
        child: AlertDialog(
          scrollable: true,
          title: Text(widget.lesson == null ? 'Place lesson' : 'Edit lesson'),
          content: SizedBox(
            width: 600,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                DropdownButtonFormField<bool>(
                  key: ValueKey('scope:$groupMode'),
                  initialValue: groupMode,
                  isExpanded: true,
                  decoration: const InputDecoration(labelText: 'Lesson for'),
                  items: const [
                    DropdownMenuItem(
                      value: true,
                      child: Text('Teaching group'),
                    ),
                    DropdownMenuItem(value: false, child: Text('Homeroom')),
                  ],
                  onChanged: enabled
                      ? (value) => _change({
                          'teaching_group_id': null,
                          'class_section_id': value == false
                              ? config.homerooms.firstOrNull?.id
                              : null,
                        })
                      : null,
                ),
                const SizedBox(height: 16),
                if (groupMode)
                  _choice(
                    'Teaching group',
                    'teaching_group_id',
                    {for (final group in state.groups) group.id: group.name},
                    state,
                    historical: widget.lesson?.title,
                  )
                else
                  _choice(
                    'Homeroom',
                    'class_section_id',
                    {for (final room in config.homerooms) room.id: room.name},
                    state,
                    historical: widget.lesson?.title,
                  ),
                const SizedBox(height: 16),
                _choice(
                  'Teacher',
                  'teacher_id',
                  {
                    for (final teacher in config.teachers)
                      teacher.id: teacher.name,
                  },
                  state,
                  historical: widget.lesson?.teacherName,
                ),
                const SizedBox(height: 16),
                _choice(
                  'Room',
                  'room_id',
                  {
                    for (final room in config.rooms.where(
                      (room) => room.active,
                    ))
                      room.id:
                          '${room.name}${room.building == null ? '' : ' · ${room.building}'}',
                  },
                  state,
                  historical: widget.lesson?.roomName,
                ),
                const SizedBox(height: 16),
                _choice(
                  'Period',
                  'bell_period_id',
                  {
                    for (final schedule in config.bellSchedules)
                      for (final period in schedule.periods.where(
                        (period) => !period.isBreak,
                      ))
                        period.id:
                            '${schedule.section} · ${period.name} · ${period.startsAt}–${period.endsAt}',
                  },
                  state,
                  historical: widget.lesson?.periodName,
                ),
                const SizedBox(height: 16),
                _choice('Day', 'weekday', {
                  for (var i = 0; i < 7; i++)
                    i + 1: const [
                      'Monday',
                      'Tuesday',
                      'Wednesday',
                      'Thursday',
                      'Friday',
                      'Saturday',
                      'Sunday',
                    ][i],
                }, state),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  key: ValueKey('pattern:${draft['week_pattern']}'),
                  initialValue: draft['week_pattern'] as String,
                  isExpanded: true,
                  decoration: const InputDecoration(labelText: 'Weeks'),
                  items: const [
                    DropdownMenuItem(value: 'all', child: Text('All weeks')),
                    DropdownMenuItem(value: 'odd', child: Text('Odd weeks')),
                    DropdownMenuItem(value: 'even', child: Text('Even weeks')),
                  ],
                  onChanged: enabled
                      ? (value) => _change({'week_pattern': value})
                      : null,
                ),
                const SizedBox(height: 16),
                _date('Starts on', 'starts_on', state),
                const SizedBox(height: 16),
                _date('Ends on', 'ends_on', state),
                const SizedBox(height: 16),
                _date(
                  'Specific date (optional)',
                  'occurs_on',
                  state,
                  optional: true,
                ),
                if (widget.lesson != null)
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('Lesson active'),
                    subtitle: const Text(
                      'Cancelling preserves the saved placement and its history.',
                    ),
                    value: draft['active'] as bool,
                    onChanged: state.busy
                        ? null
                        : (value) => _change(
                            value
                                ? {'active': true}
                                : {..._original, 'active': false},
                          ),
                  ),
                if (state.error != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      state.error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                if (state.busy) const LinearProgressIndicator(),
                if (state.preview != null) ...[
                  const SizedBox(height: 16),
                  Text(
                    state.preview!.canSave
                        ? (draft['active'] == true
                              ? 'No clashes. Ready to save.'
                              : 'Ready to cancel this lesson.')
                        : 'Resolve these clashes before saving.',
                  ),
                  for (final clash in state.preview!.conflicts)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        '${clash.date ?? ''} · ${clash.reasons.join(', ')}',
                      ),
                    ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: state.busy ? null : _close,
              child: const Text('Close'),
            ),
            OutlinedButton(
              onPressed: state.busy ? null : cubit.preview,
              child: const Text('Preview placement'),
            ),
            FilledButton(
              onPressed: state.busy || state.preview?.canSave != true
                  ? null
                  : () async {
                      final saved = await cubit.save();
                      if (context.mounted && saved) Navigator.pop(context);
                    },
              child: const Text('Save timetable'),
            ),
          ],
        ),
      );
    },
  );
}
