import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import '../integration_test/support/support_fixture.dart';
import 'facilitator_widgets_test.dart' show frame, fill, tap, field;

class _Context implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'feature_flags': {'module_switches': true},
    'modules': [],
  });
}

void main() {
  testWidgets(
    'disabled support refuses its direct route without a private reader',
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
              const RouteSettings(name: Routes.supportPlans),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
      expect(find.byType(SupportScreen), findsNothing);
    },
  );
  test('panel support URLs resolve to the guarded app route', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(
      parser.parsePath('/support-plans'),
      const DeepLinkTarget(Routes.supportPlans),
    );
  });
  testWidgets(
    'counsellor shell opens support and refresh removes withdrawn case data',
    (tester) async {
      final fixture = SupportStaffFixture();
      final repo = await frame(tester, fixture.api, 390);
      await fill(tester);
      await tap(tester, find.text('Sign in'));
      await tap(tester, find.text('Support plans'));
      await tester.tap(find.byType(DropdownButtonFormField<int>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic Learner').last);
      await tester.pumpAndSettle();
      await tester.enterText(field('SIAS stage'), 'SNA1');
      await tester.enterText(field('Support needs'), 'Private support needs');
      await tester.enterText(field('Accommodations'), 'Private full plan');
      tester.testTextInput.hide();
      await tester.pumpAndSettle();
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pumpAndSettle();
      final save = find.widgetWithText(FilledButton, 'Save support plan');
      await tester.ensureVisible(save);
      await tester.pumpAndSettle();
      await tester.tap(save);
      await tester.pumpAndSettle();
      expect(find.text('Private support needs'), findsWidgets);
      expect(fixture.hasPlan, isTrue);
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/pastoral/support/'))
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
      expect(find.text('Private support needs'), findsNothing);
      await tap(tester, find.text('Support plans'));
      expect(
        find.text('No support plans in your current caseload.'),
        findsOneWidget,
      );
      expect(find.text('Private support needs'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
}
