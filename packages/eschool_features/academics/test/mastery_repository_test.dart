import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'mastery_test_repository.dart';

void main() {
  ApiMasteryRepository repository(
    FakeApi api, {
    bool Function()? active,
    String Function()? token,
    bool bearer = true,
  }) => ApiMasteryRepository(
    ApiClient(
      authToken: token ?? () => 'synthetic',
      sendsBearerToken: bearer,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'family transport pins school and rejects a response for another learner or subject',
    () async {
      for (final data in [
        masteryFixture(learner: 13),
        masteryFixture(subject: 99),
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/growth/mastery/learners/11': (RequestOptions request) {
              expect(request.headers['X-School-Id'], '1');
              expect(request.headers['X-Account-Id'], '12');
              expect(request.queryParameters['subject_id'], 4);
              return {'error': false, 'data': data};
            },
          },
        );
        await expectLater(
          repository(api).family(11, 4),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test(
    'cookie account replacement is refused using the pinned expected-account header',
    () async {
      var cookieAccount = 12;
      final api = FakeApi(
        routes: {
          'GET v2/growth/mastery/learners/11': (RequestOptions request) {
            expect(request.headers['X-Account-Id'], '12');
            expect(request.headers.containsKey('Authorization'), isFalse);
            return request.headers['X-Account-Id'] == '$cookieAccount'
                ? {'error': false, 'data': masteryFixture()}
                : const FakeResponse({
                    'message': 'Account changed',
                  }, status: 403);
          },
        },
      );
      final repo = repository(api, bearer: false);
      expect((await repo.family(11, 4)).learnerId, 11);
      cookieAccount = 99;
      await expectLater(
        repo.family(11, 4),
        throwsA(
          isA<ApiException>().having((e) => e.statusCode, 'statusCode', 403),
        ),
      );
    },
  );
  test(
    'membership or token change while fetching discards the late private response',
    () async {
      for (final changeToken in [false, true]) {
        var active = true, token = 'synthetic';
        final api = FakeApi(
          routes: {
            'GET v2/growth/mastery/learners/11': (RequestOptions request) {
              if (changeToken) {
                token = 'different-account';
              } else {
                active = false;
              }
              return {'error': false, 'data': masteryFixture()};
            },
          },
        );
        final repo = repository(api, active: () => active, token: () => token);
        await expectLater(repo.family(11, 4), throwsA(isA<ApiException>()));
        expect(repo.isActive, isFalse);
      }
    },
  );
  test(
    'wrong audience cannot substitute a staff view for a family response',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/growth/mastery/learners/11': {
            'error': false,
            'data': masteryFixture(staff: true),
          },
        },
      );
      await expectLater(
        repository(api).family(11, 4),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
