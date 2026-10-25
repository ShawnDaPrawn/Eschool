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
  }) => ApiMasteryRepository(
    ApiClient(
      authToken: token ?? () => 'synthetic',
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
