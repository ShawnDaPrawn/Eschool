import 'dart:async';

import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/customNotification.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/ui/widgets/familyConsentNoticeAction.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Unsigned extends AuthRepository {
  @override
  bool getIsLogIn() => false;
}

class _Auth extends AuthCubit {
  _Auth(bool parent) : super(_Unsigned()) {
    emit(
      Authenticated(
        jwtToken: '',
        isStudent: !parent,
        parent: Parent.fromJson({}),
        student: Student.fromJson({}),
      ),
    );
  }
}

class _Context implements MeContextRepository {
  String role = 'Parent';
  bool enabled = true;
  Completer<MeContext?>? pending;
  MeContext value() => MeContext.fromJson({
    'user': {
      'id': 7,
      'first_name': 'Synthetic',
      'last_name': 'Actor',
      'name': 'Synthetic Actor',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school': {
      'id': 1,
      'name': 'Synthetic school',
      'short_name': null,
      'crest_url': null,
      'tagline': null,
      'timezone': 'Africa/Johannesburg',
      'branding': {'theme_color': null, 'secondary_color': null},
      'session_year': null,
    },
    'feature_flags': {'module_switches': true},
    'modules': [
      if (enabled) {'key': 'family_forms', 'read_only': false},
    ],
  });
  @override
  Future<MeContext?> fetchContext() async => pending?.future ?? value();
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
  TestWidgetsFlutterBinding.ensureInitialized();
  late AppLocalization labels;
  setUpAll(() async {
    labels = await const AppLocalizationDelegate([
      AppLanguage(languageCode: 'en', languageName: 'English'),
    ]).load(const Locale('en'));
  });
  test('optional notification recipient fields preserve installed history', () {
    final legacy = CustomNotification.fromJson({'id': 1, 'type': 'custom'});
    expect(legacy.schoolId, isNull);
    expect(legacy.readerId, isNull);
    expect(legacy.toJson().containsKey('school_id'), isFalse);
    expect(legacy.toJson().containsKey('reader_id'), isFalse);
    final current = CustomNotification.fromJson({
      'id': 73,
      'type': 'consent.requested',
      'school_id': '1',
      'reader_id': 7,
    });
    expect(current.schoolId, 1);
    expect(current.readerId, 7);
    expect(current.toJson()['reader_id'], 7);
  });
  for (final (role, parent, enabled, school, reader, visible) in [
    ('Parent', true, true, 1, 7, true),
    ('Student', false, true, 1, 7, false),
    ('Parent', false, true, 1, 7, false),
    ('Parent', true, false, 1, 7, false),
    ('Parent', true, true, 2, 7, false),
    ('Parent', true, true, 1, 8, false),
    ('Parent', true, true, null, 7, false),
    ('Parent', true, true, 1, null, false),
  ]) {
    testWidgets(
      'consent action binds realm/role/module/recipient $role/$parent/$enabled/$school/$reader',
      (tester) async {
        final repo = _Context()
          ..role = role
          ..enabled = enabled;
        final session = MeContextCubit(
              repository: repo,
              cache: InMemoryMeContextCache(),
            ),
            auth = _Auth(parent);
        addTearDown(session.close);
        addTearDown(auth.close);
        await session.load();
        expect(session.context?.currentSchoolId, 1);
        expect(session.context?.user?.id, 7);
        String? opened;
        await tester.pumpWidget(
          MultiBlocProvider(
            providers: [
              BlocProvider.value(value: session),
              BlocProvider<AuthCubit>.value(value: auth),
            ],
            child: MaterialApp(
              locale: const Locale('en'),
              supportedLocales: const [Locale('en')],
              localizationsDelegates: [_ReadyLabels(labels)],
              onGenerateRoute: (settings) {
                opened = settings.name;
                return MaterialPageRoute<void>(
                  builder: (_) => const Scaffold(
                    body: Text('Fresh family route requested'),
                  ),
                );
              },
              home: Scaffold(
                body: FamilyConsentNoticeAction(
                  noticeId: 73,
                  schoolId: school,
                  readerId: reader,
                ),
              ),
            ),
          ),
        );
        await tester.pumpAndSettle();
        final button = find.byKey(const ValueKey('consent-notice-73'));
        expect(button, visible ? findsOneWidget : findsNothing);
        if (visible) {
          await tester.tap(button);
          await tester.pumpAndSettle();
          expect(opened, Routes.familyForms);
        }
      },
    );
  }
  testWidgets(
    'loading and equivalent-account role withdrawal immediately hide a stale notice action',
    (tester) async {
      final repo = _Context(), auth = _Auth(true);
      final session = MeContextCubit(
        repository: repo,
        cache: InMemoryMeContextCache(),
      );
      addTearDown(session.close);
      addTearDown(auth.close);
      await session.load();
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [
            BlocProvider.value(value: session),
            BlocProvider<AuthCubit>.value(value: auth),
          ],
          child: MaterialApp(
            locale: const Locale('en'),
            supportedLocales: const [Locale('en')],
            localizationsDelegates: [_ReadyLabels(labels)],
            home: const Scaffold(
              body: FamilyConsentNoticeAction(
                noticeId: 73,
                schoolId: 1,
                readerId: 7,
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('consent-notice-73')), findsOneWidget);
      final gate = Completer<MeContext?>();
      repo.pending = gate;
      final refresh = session.load();
      expect(session.state, isA<MeContextLoading>());
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('consent-notice-73')), findsNothing);
      repo.role = 'Facilitator';
      gate.complete(repo.value());
      await refresh;
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('consent-notice-73')), findsNothing);
    },
  );
  test(
    'trusted family-forms push/email path resolves only the implemented route',
    () {
      final parser = DeepLinkParser(routes: deepLinkRoutes);
      expect(
        parser
            .parse(Uri.parse('https://app.navigateed.co.za/family-forms'))
            ?.routeName,
        Routes.familyForms,
      );
      expect(
        parser.parse(Uri.parse('https://untrusted.example/family-forms')),
        isNull,
      );
    },
  );
}
