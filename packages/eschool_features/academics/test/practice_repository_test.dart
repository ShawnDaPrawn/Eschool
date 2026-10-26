import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'practice_fixture.dart';

void main() {
  ApiPracticeRepository repository(
    FakeApi fake, {
    bool Function()? active,
    String Function()? token,
    bool bearer = true,
  }) => ApiPracticeRepository(
    ApiClient(
      authToken: token ?? () => 'Synthetic token',
      sendsBearerToken: bearer,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = fake,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'cookie practice reader binds exact account/school and omits private URLs from logs',
    () async {
      final messages = <String>[];
      final previous = debugPrint;
      debugPrint = (String? text, {int? wrapWidth}) {
        if (text != null) messages.add(text);
      };
      addTearDown(() => debugPrint = previous);
      final api = FakeApi(
        routes: {
          'GET v2/learning/practice/learners/11/daily-sets/10':
              (RequestOptions request) {
                expect(request.headers['X-School-Id'], '1');
                expect(request.headers['X-Account-Id'], '12');
                expect(request.headers.containsKey('Authorization'), isFalse);
                return {'error': false, 'data': practiceSet().toJson()};
              },
        },
      );
      expect(
        (await repository(
          api,
          bearer: false,
        ).daily(11, 10)).items.single.definition.prompt,
        'One half as a decimal?',
      );
      expect(
        messages.where(
          (m) => m.contains('/learners/11/') || m.contains('One half'),
        ),
        isEmpty,
      );
    },
  );
  for (final changed in ['set', 'school', 'reader']) {
    test(
      'substituted $changed projection is refused before rendering',
      () async {
        final api = FakeApi(
          routes: {
            'GET v2/learning/practice/learners/11/daily-sets/10': {
              'error': false,
              'data': practiceSet(
                id: changed == 'set' ? 99 : 10,
                school: changed == 'school' ? 2 : 1,
                reader: changed == 'reader' ? 99 : 12,
              ).toJson(),
            },
          },
        );
        await expectLater(
          repository(api).daily(11, 10),
          throwsA(isA<ApiException>()),
        );
      },
    );
  }
  test(
    'late private response cannot enter replacement bearer or account context',
    () async {
      var active = true, token = 'First token';
      final api = FakeApi(
        routes: {
          'GET v2/learning/practice/learners/11/daily-sets/10':
              (RequestOptions _) {
                active = false;
                token = 'Replacement token';
                return {'error': false, 'data': practiceSet().toJson()};
              },
        },
      );
      await expectLater(
        repository(api, active: () => active, token: () => token).daily(11, 10),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'immutable review restores the exact UUID/body and rejects malformed persisted identities',
    () {
      final value = practiceAnswer();
      final copy = value.savedReview();
      (copy['body'] as Map)['answer'] = 'changed';
      expect(value.body['answer'], '.500');
      expect(
        PracticeIntent.restoreReview(value.savedReview()).body,
        value.body,
      );
      final bad = value.savedReview();
      (bad['body'] as Map)['operation_id'] = 'not-a-uuid';
      expect(() => PracticeIntent.restoreReview(bad), throwsFormatException);
    },
  );
}
