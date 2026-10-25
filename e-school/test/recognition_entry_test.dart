import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/app/sharedScreens.dart';
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
        child: const MaterialApp(
          home: Scaffold(body: FamilyRecognitionEntry()),
        ),
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
}
