import 'dart:convert';
import 'dart:io';

import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/app/sharedScreens.dart';
import 'package:eschool/utils/diaryReminders.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  _Context({this.role = 'Student', this.module = false});
  final String role;
  final bool module;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': 7,
      'first_name': 'Synthetic',
      'last_name': 'Learner',
      'roles': [role],
    },
    'current_school_id': 1,
    'approval': {'status': 'approved'},
    'feature_flags': {'module_switches': true},
    'modules': [
      if (module) {'key': 'diary', 'read_only': false},
    ],
  });
}

Future<MeContextCubit> _session(_Context context) async {
  final session = MeContextCubit(
    repository: context,
    cache: InMemoryMeContextCache(),
  );
  await session.load();
  return session;
}

void main() {
  testWidgets('a school without the diary opens no private reader', (
    tester,
  ) async {
    final session = await _session(_Context());
    final schools = SchoolSelectionCubit(store: InMemorySelectionStore());
    addTearDown(session.close);
    addTearDown(schools.close);
    await tester.pumpWidget(
      MultiBlocProvider(
        providers: [
          BlocProvider.value(value: session),
          BlocProvider.value(value: schools),
        ],
        child: MaterialApp(
          onGenerateRoute: (_) => Routes.onGenerateRouted(
            const RouteSettings(name: Routes.learnerDiary),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(DiaryScreen), findsNothing);
    expect(
      find.text('This feature is unavailable at your school.'),
      findsOneWidget,
    );
  });

  for (final role in ['Parent', 'Teacher']) {
    testWidgets('$role gets no diary tile, even with the module on', (
      tester,
    ) async {
      final session = await _session(_Context(role: role, module: true));
      addTearDown(session.close);
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: const MaterialApp(home: Scaffold(body: DiaryHomeEntry())),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(ListTile), findsNothing);
    });
  }

  test('the diary has a literal deep link', () {
    expect(
      deepLinkRoutes.any((route) => route.path == Routes.learnerDiary),
      isTrue,
    );
  });

  test('reminder ids are stable per account and clear of random ids', () {
    final id = DeviceDiaryReminders.notificationId(1, 7, 'entry:12');
    expect(DeviceDiaryReminders.notificationId(1, 7, 'entry:12'), id);
    expect(DeviceDiaryReminders.notificationId(2, 7, 'entry:12'), isNot(id));
    expect(DeviceDiaryReminders.notificationId(1, 8, 'entry:12'), isNot(id));
    for (final key in ['entry:1', 'homework:5', 'event:9', 'holiday:2']) {
      final value = DeviceDiaryReminders.notificationId(1, 7, key);
      expect(value, greaterThanOrEqualTo(0x10000000));
      expect(value, lessThan(0x7fffffff));
    }
  });

  test('diary labels have a fallback in every offered language', () {
    for (final file
        in Directory('assets/languages')
            .listSync()
            .whereType<File>()
            .where((file) => file.path.endsWith('.json'))) {
      final labels =
          jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'My diary',
        'Add a to-do',
        'Write a note',
        'From school',
        'Talk to someone',
        'Your notes and to-dos are private. Only you can see them: not your parents, not your teachers.',
      ]) {
        expect(labels[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
