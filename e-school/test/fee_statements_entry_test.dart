import 'dart:convert';
import 'dart:io';

import 'package:eschool_fees/eschool_fees.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/app/sharedScreens.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  _Context(this.enabled, {this.role = 'Parent'});
  final bool enabled;
  final String role;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': 7,
      'first_name': 'Synthetic',
      'last_name': 'Actor',
      'name': 'Synthetic Actor',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school_id': 1,
    'feature_flags': {'module_switches': true},
    'modules': [
      if (enabled) {'key': 'fees', 'read_only': false},
    ],
  });
}

class _ReadyLabels extends LocalizationsDelegate<AppLocalization> {
  const _ReadyLabels(this.labels);
  final AppLocalization labels;
  @override
  bool isSupported(Locale locale) => locale == labels.locale;
  @override
  Future<AppLocalization> load(Locale locale) => SynchronousFuture(labels);
  @override
  bool shouldReload(_ReadyLabels old) => false;
}

void main() {
  for (final enabled in [false, true]) {
    testWidgets('fee statement entry requires explicit module $enabled', (
      tester,
    ) async {
      final session = MeContextCubit(
        repository: _Context(enabled),
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      expect(session.context?.roles, isNotEmpty);
      addTearDown(session.close);
      late AppLocalization labels;
      await tester.runAsync(() async {
        labels = await const AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]).load(const Locale('en'));
      });
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            locale: const Locale('en'),
            supportedLocales: const [Locale('en')],
            localizationsDelegates: [_ReadyLabels(labels)],
            home: Scaffold(body: FeeStatementsEntry()),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('Fee statements'),
        enabled ? findsOneWidget : findsNothing,
      );
    });
  }
  testWidgets(
    'disabled direct fee statement route never constructs a private reader',
    (tester) async {
      final session = MeContextCubit(
        repository: _Context(false),
        cache: InMemoryMeContextCache(),
      );
      final schools = SchoolSelectionCubit(store: InMemorySelectionStore());
      await session.load();
      expect(session.context?.roles, isNotEmpty);
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
              const RouteSettings(name: Routes.feeStatements),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(FeeStatementScreen), findsNothing);
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
    },
  );
  test('fee statements resolve only their exact read-only route', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(
      parser.parsePath('/fee-statements'),
      const DeepLinkTarget(Routes.feeStatements),
    );
    expect(parser.parsePath('/fee-statements/7'), isNull);
  });
  testWidgets('learner cannot see the fee statements entry', (tester) async {
    final session = MeContextCubit(
      repository: _Context(true, role: 'Student'),
      cache: InMemoryMeContextCache(),
    );
    await session.load();
    expect(session.context?.roles, isNotEmpty);
    addTearDown(session.close);
    await tester.pumpWidget(
      BlocProvider.value(
        value: session,
        child: const MaterialApp(home: Scaffold(body: FeeStatementsEntry())),
      ),
    );
    expect(find.byType(Card), findsNothing);
  });
  test('fee statement entry labels exist in every offered catalogue', () {
    for (final file in Directory(
      'assets/languages',
    ).listSync().whereType<File>().where((f) => f.path.endsWith('.json'))) {
      final json = jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'Fee statements',
        'Your learner shares, receipts and held credits',
        'Your invoice balance still due',
        'Your held credit',
        'Additional fee',
        'Family charge apportioned to this learner',
      ]) {
        expect(json[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
