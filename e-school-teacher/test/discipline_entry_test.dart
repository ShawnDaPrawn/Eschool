import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
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
  testWidgets('disabled discipline refuses direct route before private calls', (
    tester,
  ) async {
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
            const RouteSettings(name: Routes.schoolDiscipline),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.text('This feature is unavailable at your school.'),
      findsOneWidget,
    );
    expect(find.byType(SchoolDisciplineScreen), findsNothing);
  });
  test('panel discipline links resolve to guarded staff route', () {
    expect(
      DeepLinkParser(routes: deepLinkRoutes).parsePath('/school-discipline'),
      const DeepLinkTarget(Routes.schoolDiscipline),
    );
  });
}
