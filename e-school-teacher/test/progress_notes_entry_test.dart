import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import '../integration_test/support/notes_fixture.dart';
import 'facilitator_widgets_test.dart' show frame, fill, tap;

class _Context implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'feature_flags': {'module_switches': true},
    'modules': [],
  });
}

void main() {
  testWidgets(
    'disabled academics refuses direct notes route before private reader',
    (tester) async {
      final session = MeContextCubit(
        repository: _Context(),
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            onGenerateRoute: (_) => Routes.onGenerateRouted(
              const RouteSettings(name: Routes.progressNotes),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
      expect(find.byType(ProgressNotesScreen), findsNothing);
    },
  );
  test('panel notes links resolve to guarded app progress route', () {
    expect(
      DeepLinkParser(routes: deepLinkRoutes).parsePath('/learner-notes'),
      const DeepLinkTarget(Routes.progressNotes),
    );
    expect(
      DeepLinkParser(routes: deepLinkRoutes).parsePath('/growth/progress'),
      const DeepLinkTarget(Routes.progressNotes),
    );
  });
  testWidgets(
    'clinical Notes card preserves Support and refresh erases withdrawn note data',
    (tester) async {
      final fixture = NotesStaffFixture();
      final repo = await frame(tester, fixture.api, 390);
      await fill(tester);
      await tap(tester, find.text('Sign in'));
      expect(find.text('Support plans'), findsOneWidget);
      await tap(tester, find.text('Restricted counselling notes'));
      await tester.tap(find.byType(DropdownButtonFormField<int>).first);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic learner').last);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic Counsellor'));
      await tester.pumpAndSettle();
      expect(
        find.text('Private synthetic clinical observation'),
        findsOneWidget,
      );
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/growth/progress/'))
            .every(
              (r) =>
                  r.headers['X-Account-Id'] == '12' &&
                  r.headers['X-School-Id'] == '1',
            ),
        isTrue,
      );
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private synthetic clinical observation'), findsNothing);
      await tap(tester, find.text('Restricted counselling notes'));
      expect(find.text('Synthetic learner'), findsNothing);
      fixture.available = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Restricted counselling notes'), findsNothing);
      expect(find.text('Support plans'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
}
