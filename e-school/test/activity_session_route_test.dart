import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool/app/sharedScreens.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

class _ContextRepository implements MeContextRepository {
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    ...academicContextFixture(role: 'Parent'),
    'user': {
      'id': 12,
      'first_name': 'Synthetic',
      'last_name': 'Reader',
      'name': 'Synthetic Reader',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': ['Parent'],
    },
    'feature_flags': {'module_switches': true},
    'modules': [
      {'key': 'groups', 'read_only': false},
    ],
    'children': <Object>[],
  });
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory directory;
  setUpAll(() async {
    directory = await Directory.systemTemp.createTemp('group-refresh-');
    Hive.init(directory.path);
    final box = await Hive.openBox<dynamic>(SessionBoxKeys.authBox);
    await box.put(SessionBoxKeys.jwtToken, 'synthetic-activity-session');
    await box.put(SessionBoxKeys.selectedSchoolId, 1);
  });
  tearDownAll(() async {
    Api.debugCreateDio = null;
    await Hive.close();
    await directory.delete(recursive: true);
  });
  testWidgets(
    'actual Parent activity route refreshes a cancelled group despite identical account context',
    (tester) async {
      final me = MeContextCubit(
        repository: _ContextRepository(),
        cache: InMemoryMeContextCache(),
      );
      await me.load();
      final schools = SchoolSelectionCubit(
        store: InMemorySelectionStore(schoolId: 1),
      );
      final children = ChildSelectionCubit(store: InMemorySelectionStore());
      addTearDown(me.close);
      addTearDown(schools.close);
      addTearDown(children.close);
      var cancelled = false;
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups': (RequestOptions request) => {
            'error': false,
            'data': {
              'items': [
                if (!cancelled)
                  {
                    'id': 7,
                    'school_id': 1,
                    'session_year_id': 2,
                    'parent_id': null,
                    'name': 'Private Hockey Group',
                    'discipline': 'Sport',
                    'season': '2026',
                    'starts_on': '2026-01-01',
                    'ends_on': '2026-12-31',
                    'gender': 'any',
                    'minimum_grade': null,
                    'maximum_grade': null,
                    'minimum_age': null,
                    'maximum_age': null,
                    'active': true,
                    'revision': 1,
                  },
              ],
              'page': 1,
              'last_page': 1,
              'total': cancelled ? 0 : 1,
            },
          },
        },
      );
      Api.debugCreateDio = () => Dio()..httpClientAdapter = api;
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [
            BlocProvider.value(value: me),
            BlocProvider.value(value: schools),
            BlocProvider.value(value: children),
          ],
          child: MaterialApp(onGenerateRoute: (_) => activityGroupsRoute()),
        ),
      );
      await tester.pumpAndSettle();
        expect(find.text('Private Hockey Group'), findsOneWidget);
      cancelled = true;
      await me
          .load(); // Loading and identical Loaded occur before the next frame.
      await tester.pumpAndSettle();
      expect(
        find.text('Private Hockey Group', skipOffstage: false),
        findsNothing,
      );
      expect(find.text('No current activity groups.'), findsOneWidget);
      expect(api.requests, hasLength(2));
      expect(api.requests.last.headers['X-Account-Id'], '12');
      expect(tester.takeException(), isNull);
    },
  );
}
