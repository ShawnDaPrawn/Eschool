import 'package:dio/dio.dart';
import 'package:eschool_academics/src/progress_notes/progress_repository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'progress_notes_test.dart' show metadata;

void main() {
  ApiProgressRepository repository(
    FakeApi api, {
    bool Function()? active,
    bool bearer = true,
  }) => ApiProgressRepository(
    ApiClient(
      authToken: () => 'Synthetic token',
      sendsBearerToken: bearer,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'private note request pins tenant/account without printing its record URL',
    () async {
      final logged = <String>[];
      final original = debugPrint;
      debugPrint = (String? message, {int? wrapWidth}) {
        if (message != null) logged.add(message);
      };
      addTearDown(() => debugPrint = original);
      final api = FakeApi(
        routes: {
          'GET v2/growth/progress/notes/9': (RequestOptions r) {
            expect(r.headers['X-School-Id'], '1');
            expect(r.headers['X-Account-Id'], '12');
            expect(r.headers.containsKey('Authorization'), isFalse);
            return {
              'error': false,
              'data': {...metadata(), 'body': 'Private synthetic body'},
            };
          },
        },
      );
      expect(
        (await repository(api, bearer: false).note(9, 11, 'staff')).body,
        'Private synthetic body',
      );
      expect(
        logged.where(
          (line) =>
              line.contains('/notes/9') || line.contains('Private synthetic'),
        ),
        isEmpty,
      );
    },
  );
  test(
    'another learner or audience cannot substitute its body in a note read',
    () async {
      for (final replacement in [
        {'learner_id': 99},
        {'visibility': 'counselling'},
        {'id': 99},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/growth/progress/notes/9': {
              'error': false,
              'data': {...metadata(), ...replacement, 'body': 'Wrong record'},
            },
          },
        );
        await expectLater(
          repository(api).note(9, 11, 'staff'),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test(
    'fresh client scope withdrawal discards an already dispatched response',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/growth/progress/notes/9': (RequestOptions r) {
            active = false;
            return {
              'error': false,
              'data': {...metadata(), 'body': 'Late private body'},
            };
          },
        },
      );
      await expectLater(
        repository(api, active: () => active).note(9, 11, 'staff'),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'family rejects a staff audience and any other learner released response',
    () async {
      for (final data in [
        {'audience': 'staff', 'learner_id': 11, 'results': []},
        {'audience': 'family', 'learner_id': 99, 'results': []},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/growth/progress/learners/11/released-bands': {
              'error': false,
              'data': data,
            },
          },
        );
        await expectLater(
          repository(api).familyBands(11, 7, 8),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
}
