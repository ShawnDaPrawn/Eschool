import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

// Thursday 29 October 2026; its week runs from Monday 26 October.
final _now = DateTime(2026, 10, 29, 9);

Map<String, Object?> _entry(
  int id, {
  String kind = 'todo',
  String title = 'Study chapter 4',
  String? body,
  String? date = '2026-10-29',
  String? remindAt,
  bool done = false,
  String? doneOn,
  int revision = 1,
}) => {
  'id': id,
  'kind': kind,
  'title': title,
  'body': body,
  'date': date,
  'remind_at': remindAt,
  'done': done,
  'done_on': done ? (doneOn ?? '2026-10-29') : null,
  'revision': revision,
  'updated_at': '2026-10-29T08:00:00+02:00',
};

Map<String, Object?> _homework(
  int id, {
  String title = 'Fraction worksheet',
  String date = '2026-10-29',
  String dueAt = '2026-10-29 14:00',
  bool submitted = false,
  String? status,
  int? points,
  bool overdue = false,
  bool done = false,
  String? note,
  String? remindAt,
}) => {
  'source': 'homework',
  'id': id,
  'title': title,
  'date': date,
  'due_at': dueAt,
  'end_date': null,
  'subject': {'id': 3, 'name': 'Mathematics'},
  'homework': {
    'submitted': submitted,
    'status': status,
    'points': points,
    'total': 10,
    'overdue': overdue,
  },
  'state': {
    'done': done || submitted,
    'done_at': null,
    'remind_at': remindAt,
    'note': note,
  },
};

Map<String, Object?> _event(int id, {String date = '2026-10-30'}) => {
  'source': 'event',
  'id': id,
  'title': 'Sports day',
  'date': date,
  'due_at': null,
  'end_date': '2026-10-31',
  'subject': null,
  'homework': null,
  'state': {'done': false, 'done_at': null, 'remind_at': null, 'note': null},
};

Map<String, Object?> _range({
  List<Map<String, Object?>> entries = const [],
  List<Map<String, Object?>> items = const [],
  int school = 1,
  int reader = 7,
  String from = '2026-10-26',
  String to = '2026-11-01',
}) => {
  'school_id': school,
  'reader_id': reader,
  'today': '2026-10-29',
  'from': from,
  'to': to,
  'entries': entries,
  'school_items': items,
};

class _FakeDiary implements DiaryRepository {
  _FakeDiary({this.entries = const [], this.items = const []});
  List<Map<String, Object?>> entries;
  List<Map<String, Object?>> items;
  bool active = true;
  final ranges = <String>[];
  final created = <(String, DiaryDraft)>[];
  final updated = <(int, int, DiaryDraft)>[];
  final deleted = <int>[];
  final states = <(String, bool, String?)>[];
  @override
  int get schoolId => 1;
  @override
  int get readerId => 7;
  @override
  bool get isActive => active;
  @override
  Future<DiaryRange> range(String from, String to) async {
    ranges.add('$from..$to');
    return DiaryRange.fromJson(
      _range(entries: entries, items: items, from: from, to: to),
    );
  }

  /// What the server's reminder list holds: open entries with a reminder.
  @override
  Future<DiaryReminders> reminders() async => DiaryReminders.fromJson({
    'school_id': 1,
    'reader_id': 7,
    'reminders': [
      for (final e in entries)
        if (e['remind_at'] != null && e['done'] != true)
          {
            'key': 'entry:${e['id']}',
            'at': '${(e['remind_at'] as String).replaceFirst(' ', 'T')}:00Z',
            'text': e['title'],
          },
    ],
  });

  @override
  Future<DiaryEntry> create(String operationId, DiaryDraft draft) async {
    created.add((operationId, draft));
    final entry = _entry(
      90 + created.length,
      kind: draft.todo ? 'todo' : 'note',
      title: draft.title.trim(),
      body: draft.body,
      date: draft.date,
      remindAt: draft.remindAt,
    );
    entries = [...entries, entry];
    return DiaryEntry.fromJson(entry);
  }

  @override
  Future<DiaryEntry> update(int id, int revision, DiaryDraft draft) async {
    updated.add((id, revision, draft));
    final entry = _entry(
      id,
      kind: draft.todo ? 'todo' : 'note',
      title: draft.title,
      date: draft.date,
      done: draft.done,
      revision: revision + 1,
    );
    entries = [
      for (final e in entries)
        if (e['id'] == id) entry else e,
    ];
    return DiaryEntry.fromJson(entry);
  }

  @override
  Future<void> delete(int id) async {
    deleted.add(id);
    entries = entries.where((e) => e['id'] != id).toList();
  }

  @override
  Future<DiaryItemState> setItemState(
    DiarySchoolItem item, {
    required bool done,
    String? remindAt,
    String? note,
  }) async {
    states.add((diaryItemKey(item), done, note));
    return DiaryItemState.fromJson({
      'source': item.source_.value,
      'id': item.id,
      'done': done,
      'remind_at': remindAt,
      'note': note,
    });
  }
}

class _Reminders implements DiaryPhoneReminders {
  final synced = <List<String>>[];
  @override
  Future<void> replaceAll(
    int schoolId,
    int readerId,
    List<DiaryReminder> reminders,
  ) async => synced.add([
    for (final reminder in reminders)
      '$schoolId/$readerId ${reminder.key} ${reminder.at.toIso8601String()}',
  ]);
}

ApiClient _client(FakeApi api) => ApiClient(
  authToken: () => 'synthetic-token',
  onUnauthorized: () {},
  sendsBearerToken: false,
  createDio: () => Dio()..httpClientAdapter = api,
);

Object? _body(RequestOptions request) =>
    request.data is String ? jsonDecode(request.data as String) : request.data;

Future<void> _mount(
  WidgetTester tester,
  _FakeDiary diary, {
  _Reminders? reminders,
  Future<void> Function()? talk,
  bool Function()? canWrite,
}) async {
  // Tall enough that every row of a day is on screen (rows only in a
  // scrollable's cache count as offstage to finders).
  tester.view.physicalSize = const Size(1080, 4800);
  tester.view.devicePixelRatio = 2;
  addTearDown(tester.view.reset);
  await tester.pumpWidget(
    MaterialApp(
      home: DiaryScreen(
        repository: diary,
        reminders: reminders,
        onTalkToSomeone: talk,
        canWrite: canWrite,
        now: () => _now,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  // Name a failing widget test in a CI annotation as well as the log.
  final report = reportTestException;
  reportTestException = (details, description) {
    // ignore: avoid_print
    print(
      '::error title=Diary test failed::$description: '
      '${details.exceptionAsString().split('\n').first}',
    );
    report(details, description);
  };

  group('repository', () {
    test('reads one week for the pinned learner and school only', () async {
      for (final wrong in [false, true]) {
        final api = FakeApi(
          routes: {
            'GET v2/diary': {
              'error': false,
              'data': _range(reader: wrong ? 99 : 7),
            },
          },
        );
        final repo = ApiDiaryRepository(
          _client(api),
          schoolId: 1,
          readerId: 7,
          contextActive: () => true,
        );
        if (wrong) {
          await expectLater(
            repo.range('2026-10-26', '2026-11-01'),
            throwsA(isA<ApiException>()),
          );
        } else {
          final range = await repo.range('2026-10-26', '2026-11-01');
          expect(range.readerId, 7);
        }
        final request = api.requests.single;
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '7');
        expect(request.queryParameters, {
          'from': '2026-10-26',
          'to': '2026-11-01',
        });
      }
    });

    test('writes send exactly the documented fields', () async {
      final api = FakeApi(
        routes: {
          'POST v2/diary/entries': {'error': false, 'data': _entry(5)},
          'PUT v2/diary/entries/5': {
            'error': false,
            'data': _entry(5, done: true, revision: 2),
          },
          'DELETE v2/diary/entries/5': {
            'error': false,
            'data': {'id': 5},
          },
          'PUT v2/diary/items/homework/3/state': {
            'error': false,
            'data': {
              'source': 'homework',
              'id': 3,
              'done': true,
              'remind_at': null,
              'note': 'Ask Ben',
            },
          },
        },
      );
      final repo = ApiDiaryRepository(
        _client(api),
        schoolId: 1,
        readerId: 7,
        contextActive: () => true,
      );
      const draft = DiaryDraft(
        todo: true,
        title: '  Study chapter 4  ',
        body: '   ',
        date: '2026-10-29',
        remindAt: '2026-10-29 18:00',
      );
      await repo.create('8aef50e7-2429-4d2c-a523-668ae436c4d4', draft);
      await repo.update(5, 1, draft);
      await repo.delete(5);
      await repo.setItemState(
        DiarySchoolItem.fromJson(_homework(3)),
        done: true,
        note: 'Ask Ben',
      );
      expect(_body(api.requests[0]), {
        'operation_id': '8aef50e7-2429-4d2c-a523-668ae436c4d4',
        'kind': 'todo',
        'title': 'Study chapter 4',
        'body': null,
        'date': '2026-10-29',
        'remind_at': '2026-10-29 18:00',
        'done': false,
      });
      expect(_body(api.requests[1]), {
        'revision': 1,
        'title': 'Study chapter 4',
        'body': null,
        'date': '2026-10-29',
        'remind_at': '2026-10-29 18:00',
        'done': false,
      });
      expect(api.requests[2].data, isNull);
      expect(_body(api.requests[3]), {
        'done': true,
        'remind_at': null,
        'note': 'Ask Ben',
      });
      expect(api.requestedPaths, [
        'POST v2/diary/entries',
        'PUT v2/diary/entries/5',
        'DELETE v2/diary/entries/5',
        'PUT v2/diary/items/homework/3/state',
      ]);
    });

    test('a withdrawn session or an invalid draft sends nothing', () async {
      var active = false;
      final api = FakeApi(routes: {});
      final repo = ApiDiaryRepository(
        _client(api),
        schoolId: 1,
        readerId: 7,
        contextActive: () => active,
      );
      await expectLater(
        repo.range('2026-10-26', '2026-11-01'),
        throwsA(isA<ApiException>()),
      );
      active = true;
      await expectLater(
        repo.create(
          '8aef50e7-2429-4d2c-a523-668ae436c4d4',
          const DiaryDraft(todo: false, title: 'Undated note'),
        ),
        throwsArgumentError,
      );
      await expectLater(
        repo.create(
          '8aef50e7-2429-4d2c-a523-668ae436c4d4',
          const DiaryDraft(todo: true, title: '   '),
        ),
        throwsArgumentError,
      );
      expect(api.requests, isEmpty);
    });
  });

  group('session', () {
    MeContext context({
      String role = 'Student',
      String approval = 'approved',
      bool module = true,
    }) => MeContext.fromJson({
      ...academicContextFixture(role: role),
      'user': {
        'id': 7,
        'first_name': 'Asha',
        'last_name': 'Learner',
        'name': 'Asha Learner',
        'email': 'asha@example.test',
        'image': null,
        'roles': [role],
      },
      'current_school_id': 1,
      'approval': {'status': approval},
      'feature_flags': {'module_switches': true},
      'modules': [
        if (module) {'key': 'diary', 'read_only': false},
      ],
    });

    test('only the approved learner with the module on keeps the diary', () {
      final pinned = context();
      expect(diaryContextCurrent(pinned, context()), isTrue);
      expect(diaryContextCurrent(pinned, context(role: 'Parent')), isFalse);
      expect(
        diaryContextCurrent(pinned, context(approval: 'pending')),
        isFalse,
      );
      expect(diaryContextCurrent(pinned, context(module: false)), isFalse);
      expect(diaryContextCurrent(pinned, null), isFalse);
      final parent = context(role: 'Parent');
      expect(diaryContextCurrent(parent, parent), isFalse);
    });
  });

  group('screen', () {
    testWidgets(
      'today shows school items, own to-dos and notes, and anytime to-dos',
      (tester) async {
        final diary = _FakeDiary(
          entries: [
            _entry(1, title: 'Buy poster paper'),
            _entry(2, kind: 'note', title: 'Feeling nervous', body: 'Revise'),
            _entry(3, title: 'Return library book', date: null),
            _entry(
              5,
              title: 'Done on Friday',
              date: null,
              done: true,
              doneOn: '2026-10-30',
            ),
            _entry(4, title: 'Next week thing', date: '2026-10-30'),
          ],
          items: [
            _homework(10),
            _homework(
              11,
              title: 'Old essay',
              dueAt: '2026-10-20 08:00',
              overdue: true,
            ),
            _event(12),
          ],
        );
        await _mount(tester, diary);
        expect(diary.ranges, ['2026-10-26..2026-11-01']);
        expect(find.text('Thursday, October 29, 2026'), findsOneWidget);
        expect(find.text('Fraction worksheet'), findsOneWidget);
        expect(find.text('Old essay'), findsOneWidget);
        expect(find.textContaining('Late: not handed in yet'), findsOneWidget);
        expect(find.text('Buy poster paper'), findsOneWidget);
        expect(find.text('Feeling nervous'), findsOneWidget);
        expect(find.text('Return library book'), findsOneWidget);
        expect(find.text('Next week thing'), findsNothing);
        expect(find.text('Done on Friday'), findsNothing);
        expect(find.text('Sports day'), findsNothing);
        expect(find.textContaining('Only you can see them'), findsOneWidget);

        // Friday: the event and its dated to-do; anytime to-dos stay on today.
        await tester.tap(find.byKey(const ValueKey('diary-day-2026-10-30')));
        await tester.pumpAndSettle();
        expect(find.text('Sports day'), findsOneWidget);
        expect(find.text('Next week thing'), findsOneWidget);
        expect(find.text('Done on Friday'), findsOneWidget);
        expect(find.text('Return library book'), findsNothing);
        expect(find.text('Fraction worksheet'), findsNothing);
        // Saturday still shows the two-day event.
        await tester.tap(find.byKey(const ValueKey('diary-day-2026-10-31')));
        await tester.pumpAndSettle();
        expect(find.text('Sports day'), findsOneWidget);
        expect(diary.ranges, hasLength(1), reason: 'the week is already here');
      },
    );

    for (final kind in ['note', 'todo']) {
      testWidgets(
        'read-only $kind opens its full text without write controls',
        (tester) async {
          final body = List.generate(20, (i) => 'Private line $i').join('\n');
          final diary = _FakeDiary(
            entries: [
              _entry(1, kind: kind, title: 'Full private entry', body: body),
            ],
          );
          await _mount(tester, diary, canWrite: () => false);
          await tester.tap(find.text('Full private entry'));
          await tester.pumpAndSettle();
          final text = tester.widget<Text>(find.text(body));
          expect(text.maxLines, isNull);
          expect(find.byKey(const ValueKey('diary-save')), findsNothing);
          expect(find.byKey(const ValueKey('diary-delete')), findsNothing);
          expect(diary.updated, isEmpty);
          expect(diary.deleted, isEmpty);
        },
      );
    }

    testWidgets('ticking a to-do and a school item saves only that state', (
      tester,
    ) async {
      final diary = _FakeDiary(
        entries: [_entry(1, title: 'Buy poster paper')],
        items: [
          _homework(10),
          _homework(11, title: 'Handed in already', submitted: true),
        ],
      );
      await _mount(tester, diary);
      await tester.tap(
        find.descendant(
          of: find.byKey(const ValueKey('diary-entry-1')),
          matching: find.byType(Checkbox),
        ),
      );
      await tester.pumpAndSettle();
      expect(diary.updated.single.$1, 1);
      expect(diary.updated.single.$3.done, isTrue);
      expect(diary.updated.single.$3.title, 'Buy poster paper');

      await tester.tap(
        find.descendant(
          of: find.byKey(const ValueKey('diary-item-homework:10')),
          matching: find.byType(Checkbox),
        ),
      );
      await tester.pumpAndSettle();
      expect(diary.states.single, ('homework:10', true, null));

      final submitted = tester.widget<Checkbox>(
        find.descendant(
          of: find.byKey(const ValueKey('diary-item-homework:11')),
          matching: find.byType(Checkbox),
        ),
      );
      expect(submitted.value, isTrue);
      expect(submitted.onChanged, isNull, reason: 'the school says it is in');
    });

    testWidgets('a new to-do is written once and appears on the day', (
      tester,
    ) async {
      final diary = _FakeDiary();
      await _mount(tester, diary);
      expect(find.byKey(const ValueKey('diary-empty')), findsOneWidget);
      await tester.ensureVisible(find.byKey(const ValueKey('diary-add-todo')));
      await tester.tap(find.byKey(const ValueKey('diary-add-todo')));
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.byKey(const ValueKey('diary-save')));
      await tester.tap(find.byKey(const ValueKey('diary-save')));
      await tester.pumpAndSettle();
      expect(find.text('Write a title.'), findsOneWidget);
      expect(diary.created, isEmpty);

      await tester.enterText(
        find.byKey(const ValueKey('diary-title')),
        'Finish science project',
      );
      await tester.ensureVisible(find.byKey(const ValueKey('diary-save')));
      await tester.tap(find.byKey(const ValueKey('diary-save')));
      await tester.pumpAndSettle();
      expect(diary.created, hasLength(1));
      expect(diary.created.single.$2.date, '2026-10-29');
      expect(diary.created.single.$2.todo, isTrue);
      expect(find.text('Finish science project'), findsOneWidget);
      expect(find.text('Saved.'), findsOneWidget);
    });

    testWidgets('a note can be deleted for good after confirming', (
      tester,
    ) async {
      final diary = _FakeDiary(
        entries: [_entry(2, kind: 'note', title: 'Private thought')],
      );
      await _mount(tester, diary);
      await tester.tap(find.text('Private thought'));
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.byKey(const ValueKey('diary-delete')));
      await tester.tap(find.byKey(const ValueKey('diary-delete')));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Keep it'));
      await tester.pumpAndSettle();
      expect(diary.deleted, isEmpty);
      await tester.ensureVisible(find.byKey(const ValueKey('diary-delete')));
      await tester.tap(find.byKey(const ValueKey('diary-delete')));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(TextButton, 'Delete').last);
      await tester.pumpAndSettle();
      expect(diary.deleted, [2]);
      expect(find.text('Private thought'), findsNothing);
    });

    testWidgets('the phone keeps exactly the server\'s upcoming reminders', (
      tester,
    ) async {
      final reminders = _Reminders();
      final diary = _FakeDiary(
        entries: [_entry(1, title: 'Revise', remindAt: '2026-10-29 18:00')],
      );
      await _mount(tester, diary, reminders: reminders);
      expect(reminders.synced, [
        ['1/7 entry:1 2026-10-29T18:00:00.000Z'],
      ]);
      // Ticking it off syncs again, and the done to-do has no reminder.
      await tester.tap(
        find.descendant(
          of: find.byKey(const ValueKey('diary-entry-1')),
          matching: find.byType(Checkbox),
        ),
      );
      await tester.pumpAndSettle();
      expect(reminders.synced.last, isEmpty);
    });

    testWidgets('read-only, talk to someone, and nothing left in background', (
      tester,
    ) async {
      var talked = 0;
      final diary = _FakeDiary(entries: [_entry(1, title: 'Secret plan')]);
      await _mount(
        tester,
        diary,
        canWrite: () => false,
        talk: () async => talked++,
      );
      expect(find.byKey(const ValueKey('diary-add-todo')), findsNothing);
      expect(find.text('Your diary is read-only right now.'), findsOneWidget);
      await tester.ensureVisible(find.byKey(const ValueKey('diary-talk')));
      await tester.tap(find.byKey(const ValueKey('diary-talk')));
      expect(talked, 1);

      await tester.pumpAndSettle();

      // Cleared on `inactive`, while frames still draw (the app switcher's
      // snapshot is taken then).
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Secret plan'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Secret plan'), findsOneWidget);
    });

    testWidgets('a withdrawn session shows no diary text', (tester) async {
      final diary = _FakeDiary(entries: [_entry(1, title: 'Secret plan')]);
      await _mount(tester, diary);
      expect(find.text('Secret plan'), findsOneWidget);
      diary.active = false;
      await tester.tap(find.byTooltip('Refresh'));
      await tester.pumpAndSettle();
      expect(find.text('Secret plan'), findsNothing);
      expect(find.byKey(const ValueKey('diary-closed')), findsOneWidget);
    });
  });

  testWidgets(
    'the boundary keeps an open editor through a refresh, closes it on a change',
    (tester) async {
      final repo = _ContextRepo();
      final session = MeContextCubit(
        repository: repo,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: DiarySessionBoundary(
              pinned: session.context!,
              builder: (context, _) => Scaffold(
                body: TextButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) =>
                          const Scaffold(body: Text('Private editor canary')),
                    ),
                  ),
                  child: const Text('Private diary canary'),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.pump();
      await tester.tap(find.text('Private diary canary'));
      await tester.pumpAndSettle();
      expect(find.text('Private editor canary'), findsOneWidget);

      // The same learner's context, refreshed: the editor stays.
      await session.load();
      await tester.pumpAndSettle();
      expect(find.text('Private editor canary'), findsOneWidget);

      // Away from the front: covered.
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.byKey(const ValueKey('diary-privacy-cover')), findsOneWidget);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pump();
      expect(find.byKey(const ValueKey('diary-privacy-cover')), findsNothing);

      // Another role: the diary and the editor above it are both gone.
      repo.role = 'Parent';
      await session.load();
      await tester.pumpAndSettle();
      expect(find.text('Private editor canary'), findsNothing);
      expect(find.text('Private diary canary'), findsNothing);
    },
  );
}

class _ContextRepo implements MeContextRepository {
  String role = 'Student';
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    ...academicContextFixture(role: role),
    'user': {
      'id': 7,
      'first_name': 'Asha',
      'last_name': 'Learner',
      'name': 'Asha Learner',
      'email': 'asha@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school_id': 1,
    'approval': {'status': 'approved'},
    'feature_flags': {'module_switches': true},
    'modules': [
      {'key': 'diary', 'read_only': false},
    ],
  });
}
