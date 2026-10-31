import '../localization/academic_text.dart';
import 'dart:math' as math;
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'timetable_cubit.dart';
import 'timetable_repository.dart';
import 'timetable_editor.dart';

class TimetableScreen extends StatelessWidget {
  const TimetableScreen({required this.title, super.key});
  final String title;
  static Widget withRepository(
    TimetableRepository repository, {
    int? learner,
    required String title,
  }) => BlocProvider(
    create: (_) => TimetableCubit(repository, learner: learner)..load(),
    child: TimetableScreen(title: title),
  );

  Future<void> _edit(
    BuildContext context, [
    AcademicTimetableLesson? lesson,
  ]) async {
    final cubit = context.read<TimetableCubit>();
    cubit.edit(lesson);
    if (cubit.state.draft == null) return;
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => BlocProvider.value(
        value: cubit,
        child: TimetableEditor(lesson: lesson),
      ),
    );
  }

  Widget _lesson(
    BuildContext context,
    AcademicTimetableLesson lesson,
    bool manage,
  ) => Card(
    child: Padding(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${lesson.startsAt}–${lesson.endsAt}',
            style: Theme.of(context).textTheme.labelLarge,
          ),
          Text(lesson.title, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: 4),
          Text(lesson.roomName),
          Text(lesson.teacherName),
          if (manage)
            TextButton(
              onPressed: () => _edit(context, lesson),
              child: const AcademicText('Edit lesson'),
            ),
        ],
      ),
    ),
  );
  List<String> _days(String start) => List.generate(
    7,
    (index) => DateTime.parse(
      '${start}T12:00:00Z',
    ).add(Duration(days: index)).toIso8601String().substring(0, 10),
  );
  String _day(BuildContext context, String date) {
    final parsed = DateTime.parse(date);
    return '${const ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][parsed.weekday - 1]} ${MaterialLocalizations.of(context).formatShortDate(parsed)}';
  }

  /// Exam mode: the school marked this day as one of the learner's exam days.
  bool _examDay(AcademicTimetableWeekData week, String day) =>
      week.examDays?.contains(day) ?? false;

  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<TimetableCubit, TimetableState>(
    builder: (context, state) {
      final cubit = context.read<TimetableCubit>();
      final week = state.week;
      final manage = week?.canManage == true && cubit.learner == null;
      final body = ScreenScaffold(
        title: title,
        body: state.loading
            ? const Center(child: CircularProgressIndicator())
            : week == null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(state.error ?? 'No timetable is available.'),
                    TextButton(
                      onPressed: cubit.load,
                      child: const AcademicText('Try again'),
                    ),
                  ],
                ),
              )
            : Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(12),
                    child: Wrap(
                      spacing: 12,
                      runSpacing: 8,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        IconButton(
                          tooltip: academicText(context, 'Previous week'),
                          onPressed: state.busy
                              ? null
                              : () => cubit.load(
                                  startsOn:
                                      DateTime.parse(
                                            '${week.startsOn}T12:00:00Z',
                                          )
                                          .subtract(const Duration(days: 7))
                                          .toIso8601String()
                                          .substring(0, 10),
                                  year: manage ? week.sessionYearId : null,
                                ),
                          icon: const Icon(Icons.chevron_left),
                        ),
                        Text(
                          '${week.startsOn} – ${week.endsOn}',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        IconButton(
                          tooltip: academicText(context, 'Next week'),
                          onPressed: state.busy
                              ? null
                              : () => cubit.load(
                                  startsOn:
                                      DateTime.parse(
                                            '${week.startsOn}T12:00:00Z',
                                          )
                                          .add(const Duration(days: 7))
                                          .toIso8601String()
                                          .substring(0, 10),
                                  year: manage ? week.sessionYearId : null,
                                ),
                          icon: const Icon(Icons.chevron_right),
                        ),
                        TextButton(
                          onPressed: state.busy ? null : cubit.load,
                          child: const AcademicText('This week'),
                        ),
                        if (manage &&
                            state.configuration?.schoolYears?.isNotEmpty ==
                                true)
                          SizedBox(
                            width: 200,
                            child: DropdownButtonFormField<int>(
                              key: ValueKey('calendar:${week.sessionYearId}'),
                              initialValue: week.sessionYearId,
                              isExpanded: true,
                              decoration: InputDecoration(
                                labelText: academicText(context, 'School year'),
                              ),
                              items: state.configuration!.schoolYears!
                                  .map(
                                    (year) => DropdownMenuItem(
                                      value: year.id,
                                      child: Text(
                                        year.name,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  )
                                  .toList(),
                              onChanged: state.busy
                                  ? null
                                  : (year) {
                                      if (year != null) {
                                        cubit.load(
                                          startsOn: week.startsOn,
                                          year: year,
                                        );
                                      }
                                    },
                            ),
                          ),
                        if (manage)
                          FilledButton.icon(
                            onPressed: state.busy ? null : () => _edit(context),
                            icon: const Icon(Icons.add),
                            label: const AcademicText('Place lesson'),
                          ),
                      ],
                    ),
                  ),
                  if (state.saved)
                    const Padding(
                      padding: EdgeInsets.all(8),
                      child: AcademicText('Timetable saved'),
                    ),
                  Expanded(
                    child: LayoutBuilder(
                      builder: (context, box) {
                        final days = _days(week.startsOn);
                        if (box.maxWidth < 700) {
                          return ListView(
                            padding: const EdgeInsets.all(12),
                            children: [
                              for (final day in days) ...[
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 12,
                                  ),
                                  child: Text(
                                    _day(context, day),
                                    style: Theme.of(
                                      context,
                                    ).textTheme.titleMedium,
                                  ),
                                ),
                                if (_examDay(week, day))
                                  const AcademicText(
                                    'Exam day: see your exam timetable',
                                  ),
                                if (!week.lessons.any(
                                  (lesson) => lesson.date == day,
                                ))
                                  const AcademicText('No lessons'),
                                for (final lesson in week.lessons.where(
                                  (lesson) => lesson.date == day,
                                ))
                                  _lesson(context, lesson, manage),
                              ],
                            ],
                          );
                        }
                        return SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: SizedBox(
                            width: math.max(box.maxWidth, 7 * 150),
                            height: box.maxHeight,
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                for (final day in days)
                                  Expanded(
                                    child: Padding(
                                      padding: const EdgeInsets.all(4),
                                      child: Column(
                                        children: [
                                          Padding(
                                            padding: const EdgeInsets.all(8),
                                            child: Text(
                                              _day(context, day),
                                              style: Theme.of(
                                                context,
                                              ).textTheme.titleSmall,
                                            ),
                                          ),
                                          Expanded(
                                            child: ListView(
                                              children: [
                                                if (_examDay(week, day))
                                                  const Center(
                                                    child: AcademicText(
                                                      'Exam day: see your exam timetable',
                                                    ),
                                                  ),
                                                if (!week.lessons.any(
                                                  (lesson) =>
                                                      lesson.date == day,
                                                ))
                                                  const Center(
                                                    child: AcademicText(
                                                      'No lessons',
                                                    ),
                                                  ),
                                                for (final lesson
                                                    in week.lessons.where(
                                                      (lesson) =>
                                                          lesson.date == day,
                                                    ))
                                                  _lesson(
                                                    context,
                                                    lesson,
                                                    manage,
                                                  ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
      );
      return AdaptiveScaffold(
        body: body,
        destinations: const [
          AdaptiveDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          AdaptiveDestination(
            icon: Icon(Icons.calendar_view_week_outlined),
            selectedIcon: Icon(Icons.calendar_view_week),
            label: 'Timetable',
          ),
        ],
        selectedIndex: 1,
        onDestinationSelected: (index) {
          if (index == 0 && !state.busy) Navigator.of(context).pop();
        },
      );
    },
  );
}
