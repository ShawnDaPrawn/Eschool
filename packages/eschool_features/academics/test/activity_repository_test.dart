import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

import 'activity_test_repository.dart';

void main() {
  Object ok(Object data) => {'error': false, 'data': data};
  ApiActivityRepository repository(
    FakeApi api, {
    bool Function()? active,
    String Function()? token,
  }) => ApiActivityRepository(
    ApiClient(
      authToken: token ?? () => 'synthetic',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    accountId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'group requests pin school/account and validate returned owner IDs',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups': ok({
            'items': [activityGroupFixture()],
            'page': 1,
            'last_page': 1,
            'total': 1,
          }),
          'GET v2/activities/groups/7': ok(activityDetailFixture()),
          'GET v2/activities/groups/7/roster': ok(activityRosterFixture()),
        },
      );
      final repo = repository(api);
      await repo.groups();
      await repo.detail(7);
      await repo.roster(7);
      expect(
        api.requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12' &&
              r.headers['Authorization'] == 'Bearer synthetic',
        ),
        isTrue,
      );
    },
  );
  test(
    'session roster pins selected session and refuses missing or mismatched response',
    () async {
      for (final altered in [
        'missing',
        'wrong-session',
        'wrong-group',
        'duplicate',
      ]) {
        final body = activityRosterFixture();
        final selected = body['session_roster'] as Map<String, dynamic>;
        if (altered == 'missing') body.remove('session_roster');
        if (altered == 'wrong-session') selected['session_id'] = 10;
        if (altered == 'wrong-group') selected['group_id'] = 99;
        if (altered == 'duplicate') {
          (selected['learners'] as List).add(
            (selected['learners'] as List).first,
          );
        }
        final api = FakeApi(
          routes: {'GET v2/activities/groups/7/roster': ok(body)},
        );
        await expectLater(
          repository(api).roster(7, sessionId: 9),
          throwsA(isA<ApiException>()),
        );
        expect(api.requests.single.queryParameters['session_id'], 9);
        expect(api.requests.single.headers['X-Account-Id'], '12');
      }
    },
  );
  test(
    'session roster preserves retained revision and valid unrecorded classmates',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups/7/roster': ok(activityRosterFixture()),
        },
      );
      final result = await repository(api).roster(7, sessionId: 9);
      expect(result.sessionRoster!.learners.map((x) => x.learnerId), [17, 18]);
      expect(result.sessionRoster!.learners.first.status, isNull);
      expect(result.sessionRoster!.learners.first.revision, 0);
    },
  );
  test(
    'foreign school detail is rejected before returning private records',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups/7': ok(activityDetailFixture(school: 2)),
        },
      );
      await expectLater(
        repository(api).detail(7),
        throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 403)),
      );
    },
  );
  test('wrong nested group material is refused', () async {
    final detail = activityDetailFixture();
    (detail['materials'] as List).single['activity_group_id'] = 99;
    await expectLater(
      repository(
        FakeApi(routes: {'GET v2/activities/groups/7': ok(detail)}),
      ).detail(7),
      throwsA(isA<ApiException>()),
    );
  });
  test(
    'school or token switch stops old repository before transport',
    () async {
      var token = 'first', active = true;
      final api = FakeApi(routes: {});
      final repo = repository(api, active: () => active, token: () => token);
      token = 'second';
      await expectLater(repo.groups(), throwsA(isA<ApiException>()));
      token = 'first';
      active = false;
      await expectLater(repo.detail(7), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test(
    'context replacement during successful response discards private data',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups/7': (RequestOptions request) {
            active = false;
            return ok(activityDetailFixture());
          },
        },
      );
      await expectLater(
        repository(api, active: () => active).detail(7),
        throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 403)),
      );
      expect(api.requests.length, 1);
    },
  );
  test('server revocation is never downgraded to empty data', () async {
    final api = FakeApi(
      routes: {
        'GET v2/activities/groups/7': FakeResponse({
          'message': 'Denied',
        }, status: 403),
      },
    );
    await expectLater(
      repository(api).detail(7),
      throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 403)),
    );
  });
  test('oversized UTF8 material is refused before request', () async {
    final api = FakeApi(routes: {});
    final input = ActivityMaterialWrite.fromJson({
      'operation_id': '4aa10dbe-a89c-4c4a-a20f-d9ef15de13ae',
      'revision': 0,
      'title': 'Draft',
      'markdown': '😀' * 17000,
      'published': false,
    });
    await expectLater(
      repository(api).material(7, null, input),
      throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 422)),
    );
    expect(api.requests, isEmpty);
  });
  test(
    'calendar dates and exact operation/revision survive session transport',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/activities/groups/7/sessions': ok(activitySessionFixture()),
        },
      );
      final input = ActivitySessionWrite.fromJson({
        'operation_id': '4aa10dbe-a89c-4c4a-a20f-d9ef15de13ae',
        'revision': 0,
        'name': 'Practice',
        'venue': 'Field',
        'on_date': '2026-02-10',
        'status': 'scheduled',
      });
      await repository(api).session(7, null, input);
      final body = api.requests.single.data as Map;
      expect(body['on_date'], '2026-02-10');
      expect(body['operation_id'], input.operationId);
      expect(body['revision'], 0);
    },
  );
}
