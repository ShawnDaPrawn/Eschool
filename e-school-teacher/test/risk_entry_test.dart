import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import '../integration_test/support/risk_fixture.dart';
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
    'disabled early warning refuses direct route before private calls',
    (tester) async {
      final context = MeContextCubit(
        repository: _Context(),
        cache: InMemoryMeContextCache(),
      );
      await context.load();
      addTearDown(context.close);
      await tester.pumpWidget(
        BlocProvider.value(
          value: context,
          child: MaterialApp(
            onGenerateRoute: (_) => Routes.onGenerateRouted(
              const RouteSettings(name: Routes.riskInterventions),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
      expect(find.byType(RiskInterventionsScreen), findsNothing);
    },
  );
  test('panel follow-up links resolve to guarded staff route', () {
    expect(
      DeepLinkParser(routes: deepLinkRoutes).parsePath('/risk-interventions'),
      const DeepLinkTarget(Routes.riskInterventions),
    );
  });
  testWidgets(
    'native follow-up preserves clinical and support cards then erases on fresh grant withdrawal',
    (tester) async {
      final fixture = RiskStaffFixture();
      final repo = await frame(tester, fixture.api, 390);
      await fill(tester);
      await tap(tester, find.text('Sign in'));
      expect(find.text('Support plans'), findsOneWidget);
      expect(find.text('Incidents and counselling'), findsOneWidget);
      expect(find.text('Restricted counselling notes'), findsOneWidget);
      await tap(tester, find.text('Staff follow-up'));
      await tap(tester, find.text('Open your intervention'));
      expect(find.text('Private synthetic follow-up action'), findsWidgets);
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/growth/risk/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private synthetic follow-up action'), findsNothing);
      await tap(tester, find.text('Staff follow-up'));
      expect(find.text('Private synthetic follow-up action'), findsNothing);
      fixture.available = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Staff follow-up'), findsNothing);
      expect(find.text('Support plans'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
}
