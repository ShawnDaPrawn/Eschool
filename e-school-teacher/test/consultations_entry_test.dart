import 'dart:convert';
import 'dart:io';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/app/sharedScreens.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  _Context(this.enabled);
  final bool enabled;
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
    'modules': [
      if (enabled) {'key': 'consultations', 'read_only': false},
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
    testWidgets('consultation entry requires explicit module $enabled', (
      tester,
    ) async {
      final session = MeContextCubit(
        repository: _Context(enabled),
        cache: InMemoryMeContextCache(),
      );
      await session.load();
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
            home: Scaffold(body: ConsultationsEntry()),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('Polls, surveys and elections'),
        enabled ? findsOneWidget : findsNothing,
      );
    });
  }
  testWidgets(
    'disabled direct consultation route never constructs a private reader',
    (tester) async {
      final session = MeContextCubit(
        repository: _Context(false),
        cache: InMemoryMeContextCache(),
      );
      final schools = SchoolSelectionCubit(store: InMemorySelectionStore());
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
              const RouteSettings(name: Routes.consultations),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(ConsultationsScreen), findsNothing);
      expect(
        find.text('This feature is unavailable at your school.'),
        findsOneWidget,
      );
    },
  );
  test('consultation notices resolve exact scoped positive references', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(
      parser.parsePath('/consultations'),
      const DeepLinkTarget(Routes.consultations),
    );
    expect(
      parser.parsePath('/consultations/9'),
      const DeepLinkTarget(Routes.consultations, arguments: 9),
    );
    expect(
      parser.parsePath('/school-consultations/9'),
      const DeepLinkTarget(Routes.consultations, arguments: 9),
    );
    for (final invalid in ['0', '-1', 'other', '9.5']) {
      expect(parser.parsePath('/consultations/$invalid'), isNull);
    }
  });
  test('consultation entry labels exist in every offered catalogue', () {
    for (final file in Directory(
      'assets/languages',
    ).listSync().whereType<File>().where((f) => f.path.endsWith('.json'))) {
      final json = jsonDecode(file.readAsStringSync()) as Map<String, dynamic>;
      for (final key in [
        'Polls, surveys and elections',
        'Record my response',
        'Check whether my response was recorded',
        'Your participation is kept apart from anonymous answers.',
      ]) {
        expect(json[key], isA<String>(), reason: '${file.path}: $key');
      }
    }
  });
}
