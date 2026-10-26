import 'package:eschool/app/routes.dart';
import 'package:eschool/ui/screens/fees/studentFeePaymentDueScreen.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('a saved lock link redirects even when the fees module is off', (
    tester,
  ) async {
    final context = MeContextCubit(
      repository: _FeesOff(),
      cache: InMemoryMeContextCache(),
    );
    addTearDown(context.close);
    await context.load();
    await tester.pumpWidget(
      BlocProvider.value(
        value: context,
        child: MaterialApp(
          initialRoute: Routes.studentFeePaymentDueScreen,
          onGenerateRoute: (settings) => settings.name == Routes.home
              ? MaterialPageRoute<void>(
                  builder: (_) => const Scaffold(body: Text('Learning home')),
                )
              : Routes.onGenerateRouted(settings),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Learning home'), findsOneWidget);
    expect(
      find.text('This feature is unavailable at your school.'),
      findsNothing,
    );
    expect(tester.takeException(), isNull);
  });

  testWidgets('a saved fee-lock route always returns to learning', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        initialRoute: '/old-fee-lock',
        routes: {
          Routes.home: (_) => const Scaffold(body: Text('Learning home')),
          '/old-fee-lock': (_) => const StudentFeePaymentDueScreen(),
        },
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Learning home'), findsOneWidget);
    expect(find.byType(StudentFeePaymentDueScreen), findsNothing);
    expect(tester.takeException(), isNull);
  });
}

class _FeesOff implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'feature_flags': {'module_switches': true},
    'modules': <Map<String, Object?>>[],
  });
}
