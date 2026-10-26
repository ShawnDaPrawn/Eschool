import 'dart:convert';
import 'dart:io';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
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
  testWidgets('disabled meeting deep route cannot construct a private reader', (
    tester,
  ) async {
    final session = MeContextCubit(
          repository: _Context(),
          cache: InMemoryMeContextCache(),
        ),
        schools = SchoolSelectionCubit(store: InMemorySelectionStore());
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
            const RouteSettings(name: Routes.familyVisits),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(FamilyVisitsScreen), findsNothing);
    expect(
      find.text('This feature is unavailable at your school.'),
      findsOneWidget,
    );
  });
  test('meeting labels have explicit fallbacks in every offered catalogue', () {
    for (final file in Directory(
      'assets/languages',
    ).listSync().whereType<File>().where((f) => f.path.endsWith('.json'))) {
      final values =
          jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'Meetings and early collection',
        'Named collector',
        'Request staff approval',
        'Your meeting access changed. Reopen this screen.',
      ]) {
        expect(values[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
