import 'package:eschool_session/eschool_session.dart';

import '../integration_test/support/recognition_fixture.dart';
import 'facilitator_widgets_test.dart' show frame, fill, tap;

import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/app/sharedScreens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'feature_flags': {'module_switches': true},
    'modules': [],
  });
}

void main() {
  testWidgets('recognition entry is absent when both modules are unavailable', (
    tester,
  ) async {
    final session = MeContextCubit(
      repository: _Context(),
      cache: InMemoryMeContextCache(),
    );
    await session.load();
    addTearDown(session.close);
    await tester.pumpWidget(
      BlocProvider.value(
        value: session,
        child: const MaterialApp(home: Scaffold(body: StaffRecognitionEntry())),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(ListTile), findsNothing);
  });
  test('recognition deep link targets guarded recognition route', () {
    expect(
      DeepLinkParser(routes: deepLinkRoutes)
          .parsePath('/community/recognition'),
      const DeepLinkTarget(Routes.recognition),
    );
  });
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'native recognition opens scoped history then erases fresh withdrawal at ${width.toInt()}px',
      (tester) async {
        final fixture = RecognitionStaffFixture();
        final repo = await frame(tester, fixture.api, width);
        await fill(tester);
        await tap(tester, find.text('Sign in'));
        expect(find.text('Activity groups'), findsOneWidget);
        await tap(tester, find.text('Houses and awards'));
        await tap(tester, find.text('Synthetic learner'));
        expect(
          find.textContaining('Private synthetic point evidence'),
          findsOneWidget,
        );
        expect(
          fixture.api.requests
              .where((r) => r.path.contains('/recognition/'))
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
        expect(
          find.textContaining('Private synthetic point evidence'),
          findsNothing,
        );
        expect(find.text('Houses and awards'), findsNothing);
        expect(find.text('Activity groups'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
