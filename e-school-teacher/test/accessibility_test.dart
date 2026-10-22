import 'dart:convert';
import 'dart:io';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/cubits/timeTableCubit.dart';
import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/class/widgets/takeAttendanceButton.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/timeTableContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

/// Today's lessons, without asking the server.
class _TodaysLessons extends TimeTableCubit {
  _TodaysLessons(List<TimeTableSlot> slots) : super(TeacherRepository()) {
    emit(TimeTableFetchSuccess(slots));
  }

  @override
  Future<void> fetchTimeTable() async {}
}

TimeTableSlot _lesson({
  required String id,
  required String start,
  required String end,
  required String subject,
  String? link,
}) => TimeTableSlot.fromJson({
  'id': id,
  'start_time': start,
  'end_time': end,
  'day': DateTime.now().weekday,
  'day_name': 'Today',
  'class_section': {
    'id': 3,
    'section': {'id': 1, 'name': 'A'},
    'class': {'id': 5, 'name': 'Grade 5'},
  },
  'subject': {
    'subject': {
      'id': int.parse(id),
      'name': subject,
      'type': 'Theory',
      // As the server sends it.
      'bg_color': '#22577A',
    },
  },
  'link_name': ?link,
  if (link != null) 'live_class_url': 'https://meet.example/$id',
});

const List<String> _labelsUsed = [
  'takeAttendance',
  'addTimetableLink',
  'editTimetableLink',
];

Future<void> _pump(WidgetTester tester, Widget child) async {
  tester.view.physicalSize = const Size(1280, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  // The app's English labels, served small: the real file is big enough that
  // loading it decodes on another isolate, which fake time never finishes.
  final Map<String, dynamic> english =
      jsonDecode(File('assets/languages/en.json').readAsStringSync())
          as Map<String, dynamic>;
  final TestDefaultBinaryMessenger messenger =
      tester.binding.defaultBinaryMessenger;
  rootBundle.evict('assets/languages/en.json');
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    if (utf8.decode(message!.buffer.asUint8List()) ==
        'assets/languages/en.json') {
      return ByteData.sublistView(
        utf8.encode(
          jsonEncode({for (final String key in _labelsUsed) key: english[key]}),
        ),
      );
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light(),
      locale: const Locale('en'),
      supportedLocales: const [Locale('en')],
      localizationsDelegates: const [
        AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]),
      ],
      home: Scaffold(body: child),
    ),
  );
  await tester.pumpAndSettle();
}

/// Self-test defect D4: the take-attendance button and the lessons on
/// Schedule could not be reached with a screen reader.
void main() {
  testWidgets('the take-attendance button has a name', (tester) async {
    final SemanticsHandle semantics = tester.ensureSemantics();
    var pressed = 0;
    await _pump(tester, TakeAttendanceButton(onPressed: () => pressed++));

    final Finder button = find.bySemanticsLabel('Take Attendance');
    expect(button, findsOneWidget);
    expect(
      tester.getSemantics(button),
      matchesSemantics(
        label: 'Take Attendance',
        isButton: true,
        hasTapAction: true,
        hasFocusAction: true,
        isFocusable: true,
        hasEnabledState: true,
        isEnabled: true,
      ),
    );
    await tester.tap(button);
    expect(pressed, 1);
    semantics.dispose();
  });

  testWidgets('each lesson on Schedule is one labelled node', (tester) async {
    final SemanticsHandle semantics = tester.ensureSemantics();
    await _pump(
      tester,
      BlocProvider<TimeTableCubit>(
        create: (_) => _TodaysLessons([
          _lesson(
            id: '1',
            start: '08:00:00',
            end: '08:45:00',
            subject: 'Mathematics',
          ),
          _lesson(
            id: '2',
            start: '08:45:00',
            end: '09:30:00',
            subject: 'English',
            link: 'Online class',
          ),
        ]),
        child: const TimeTableContainer(),
      ),
    );

    // Searched in the semantics tree, as a screen reader sees it.
    expect(
      find.semantics.byLabel('08:00 AM - 08:45 AM, Grade 5 A (), Mathematics'),
      findsOne,
    );
    expect(
      find.semantics.byLabel('08:45 AM - 09:30 AM, Grade 5 A (), English'),
      findsOne,
    );
    // The time, class and subject are read once, as part of the lesson.
    expect(find.semantics.byLabel('Mathematics'), findsNothing);
    expect(find.semantics.byLabel('08:00 AM - 08:45 AM'), findsNothing);
    expect(
      find.semantics.byLabel('Online class').evaluate().single,
      matchesSemantics(label: 'Online class', isLink: true, hasTapAction: true),
    );
    // The link actions are named buttons of their own.
    expect(
      find.semantics.byLabel('Add Timetable Link').evaluate().single,
      matchesSemantics(
        label: 'Add Timetable Link',
        isButton: true,
        hasTapAction: true,
      ),
    );
    expect(
      find.semantics.byLabel('Edit Timetable Link').evaluate().single,
      matchesSemantics(
        label: 'Edit Timetable Link',
        isButton: true,
        hasTapAction: true,
      ),
    );
    semantics.dispose();
  });
}
