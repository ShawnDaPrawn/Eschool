import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
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
  testWidgets(
    'disabled academics refuses direct released progress before private reader',
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
              const RouteSettings(name: Routes.familyProgress),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
      expect(find.byType(FamilyProgressScreen), findsNothing);
    },
  );
  test('released progress links resolve to guarded family route', () {
    expect(
      DeepLinkParser(routes: deepLinkRoutes).parsePath('/growth/progress'),
      const DeepLinkTarget(Routes.familyProgress),
    );
  });
}
