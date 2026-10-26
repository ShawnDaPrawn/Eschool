import 'dart:convert';
import 'dart:io';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/routes.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': 7,
      'first_name': 'Synthetic',
      'last_name': 'Actor',
      'roles': ['Parent'],
    },
    'current_school_id': 1,
    'feature_flags': {'module_switches': true},
    'modules': [],
  });
}

void main() {
  testWidgets(
    'disabled Exam Coach deep route cannot construct a private reader',
    (tester) async {
      final session = MeContextCubit(
        repository: _Context(),
        cache: InMemoryMeContextCache(),
      );
      final schools = SchoolSelectionCubit(store: InMemorySelectionStore());
      await session.load();
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
              const RouteSettings(name: Routes.examCoach),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(ExamScreen), findsNothing);
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
    },
  );
  test('Exam Coach labels have explicit English fallback in every offered language', () {
    for (final file in Directory(
      'assets/languages',
    ).listSync().whereType<File>().where((f) => f.path.endsWith('.json'))) {
      final values =
          jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'Exam Coach',
        'Prepare licensed paper',
        'Verified marks',
        'Use this working crop',
        'Retry exact saved request',
      ]) {
        expect(values[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
