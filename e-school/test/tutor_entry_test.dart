import 'dart:convert';
import 'dart:io';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/app/deepLinks.dart';
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
  for (final name in [Routes.tutor, Routes.tutorSummary]) {
    testWidgets(
      'disabled Tutor route $name constructs no conversation or summary reader',
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
              onGenerateRoute: (_) =>
                  Routes.onGenerateRouted(RouteSettings(name: name)),
            ),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.byType(TutorScreen), findsNothing);
        expect(find.byType(TutorSummaryScreen), findsNothing);
        expect(
          find.text('This feature is unavailable at your school.'),
          findsOneWidget,
        );
      },
    );
  }
  test('Tutor routes have literal deep links', () {
    for (final name in [Routes.tutor, Routes.tutorSummary]) {
      expect(deepLinkRoutes.any((route) => route.path == name), isTrue);
    }
  });
  test('Tutor labels have explicit fallback in all offered catalogues', () {
    for (final file
        in Directory('assets/languages')
            .listSync()
            .whereType<File>()
            .where((file) => file.path.endsWith('.json'))) {
      final labels =
          jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'Ask your tutor',
        'Tutor learning summary',
        'Use this working crop',
        'Retry the same turn',
        'Completed learning steps',
      ]) {
        expect(labels[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
