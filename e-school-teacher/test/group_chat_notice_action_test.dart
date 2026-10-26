import 'dart:async';

import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/customNotification.dart';
import 'package:eschool_teacher/ui/widgets/groupChatNoticeAction.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  String role = 'Teacher';
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
      if (enabled) {'key': 'groups', 'read_only': false},
      if (enabled) {'key': 'chat', 'read_only': false},
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
    final legacy = CustomNotification.fromJson({
      'id': 1,
      'type': 'custom',
      'date': '2026-02-10',
    });
    expect(legacy.schoolId, isNull);
    expect(legacy.readerId, isNull);
    expect(legacy.toJson().containsKey('school_id'), isFalse);
    expect(legacy.toJson().containsKey('reader_id'), isFalse);
    final current = CustomNotification.fromJson({
      'id': 73,
      'date': '2026-02-10',
      'type': 'group_chat.staff_update',
      'school_id': '1',
      'reader_id': 7,
    });
    expect(current.schoolId, 1);
    expect(current.readerId, 7);
    expect(current.toJson()['reader_id'], 7);
  });
  for (final (role, parent, enabled, school, reader, visible) in [
    ('Teacher', true, true, 1, 7, true),
    ('Facilitator', false, true, 1, 7, false),
    ('Parent', false, true, 1, 7, false),
    ('Teacher', true, false, 1, 7, false),
    ('Teacher', true, true, 2, 7, false),
    ('Teacher', true, true, 1, 8, false),
    ('Teacher', true, true, null, 7, false),
    ('Teacher', true, true, 1, null, false),
  ]) {
    testWidgets(
      'group chat action binds realm/role/module/recipient $role/$parent/$enabled/$school/$reader',
      (tester) async {
        final repo = _Context()
          ..role = role
          ..enabled = enabled;
        final session = MeContextCubit(
          repository: repo,
          cache: InMemoryMeContextCache(),
        );
        addTearDown(session.close);

        await session.load();
        expect(session.context?.currentSchoolId, 1);
        expect(session.context?.user?.id, 7);
        String? opened;
        await tester.pumpWidget(
          MultiBlocProvider(
            providers: [BlocProvider.value(value: session)],
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
                body: GroupChatNoticeAction(
                  noticeId: 73,
                  schoolId: school,
                  readerId: reader,
                ),
              ),
            ),
          ),
        );
        await tester.pumpAndSettle();
        final button = find.byKey(const ValueKey('staff-group-chat-notice-73'));
        expect(button, visible ? findsOneWidget : findsNothing);
        if (visible) {
          await tester.tap(button);
          await tester.pumpAndSettle();
          expect(opened, Routes.activityGroups);
        }
      },
    );
  }
  testWidgets(
    'loading and equivalent-account role withdrawal immediately hide a stale notice action',
    (tester) async {
      final repo = _Context();
      final session = MeContextCubit(
        repository: repo,
        cache: InMemoryMeContextCache(),
      );
      addTearDown(session.close);

      await session.load();
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [BlocProvider.value(value: session)],
          child: MaterialApp(
            locale: const Locale('en'),
            supportedLocales: const [Locale('en')],
            localizationsDelegates: [_ReadyLabels(labels)],
            home: const Scaffold(
              body: GroupChatNoticeAction(
                noticeId: 73,
                schoolId: 1,
                readerId: 7,
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.byKey(const ValueKey('staff-group-chat-notice-73')),
        findsOneWidget,
      );
      final gate = Completer<MeContext?>();
      repo.pending = gate;
      final refresh = session.load();
      expect(session.state, isA<MeContextLoading>());
      await tester.pumpAndSettle();
      expect(
        find.byKey(const ValueKey('staff-group-chat-notice-73')),
        findsNothing,
      );
      repo.role = 'Facilitator';
      gate.complete(repo.value());
      await refresh;
      await tester.pumpAndSettle();
      expect(
        find.byKey(const ValueKey('staff-group-chat-notice-73')),
        findsNothing,
      );
    },
  );
  test('trusted activities/groups push/email path resolves only the implemented route', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(
      parser
          .parse(Uri.parse('https://app.navigateed.co.za/activities/groups'))
          ?.routeName,
      Routes.activityGroups,
    );
    expect(
      parser.parse(Uri.parse('https://untrusted.example/activities/groups')),
      isNull,
    );
  });
}
