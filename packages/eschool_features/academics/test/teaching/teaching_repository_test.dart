import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

import 'teaching_fixture.dart';

ApiTeachingRepository repository(FakeApi api, {bool Function()? active}) =>
    ApiTeachingRepository(
      ApiClient(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      readerId: 7,
      contextActive: active ?? () => true,
    );
TeachingDraftGeneration request() => TeachingDraftGeneration.fromJson({
  'operation_id': teachingOperation,
  'content_version_id': 4,
  'kind': 'worksheet',
  'language': 'en',
  'brief': 'Private intent canary',
  'learner_id': null,
  'task_id': null,
  'photo_ids': <String>[],
  'topic_ids': <int>[],
});

void main() {
  test(
    'cookie transport pins account and school and rejects replacement scope',
    () async {
      for (final wrong in [false, true]) {
        final api = FakeApi(
          routes: {
            'GET v2/teaching-assistant/configuration': {
              'error': false,
              'data': {
                ...teachingConfigurationJson(),
                if (wrong) 'reader_id': 99,
              },
            },
          },
        );
        if (wrong) {
          await expectLater(
            repository(api).configuration(),
            throwsA(isA<ApiException>()),
          );
        } else {
          expect((await repository(api).configuration()).groups.single.id, 3);
        }
        expect(api.requests.single.headers['X-Account-Id'], '7');
        expect(api.requests.single.headers['X-School-Id'], '1');
        expect(
          api.requests.single.headers.containsKey('Authorization'),
          isFalse,
        );
      }
    },
  );
  test(
    'frozen generation and review retain nested bodies and original operation',
    () {
      final generation = TeachingGenerationIntent(3, request());
      final copy = generation.body;
      (copy['photo_ids'] as List).add('mutated');
      expect(generation.body['photo_ids'], isEmpty);
      final review = TeachingReviewIntent(
        teachingDraftId,
        TeachingDraftReview.fromJson({
          'operation_id': teachingOperation,
          'expected_revision': 1,
          'action': 'edit',
          'definition': teachingDetailJson()['definition'],
          'confirmed': false,
        }),
      );
      final body = review.body;
      (body['definition'] as Map)['title'] = 'changed';
      expect(
        (review.body['definition'] as Map)['title'],
        'Private worksheet canary',
      );
      expect(review.operation, teachingOperation);
    },
  );
  test(
    'generation echo must match exact group, source, kind and intent',
    () async {
      for (final key in [
        'group_id',
        'content_version_id',
        'operation_id',
        'kind',
      ]) {
        final bad = teachingDetailJson();
        bad[key] = switch (key) {
          'operation_id' => teachingPhotoId,
          'kind' => 'report_comment',
          _ => 999,
        };
        final api = FakeApi(
          routes: {
            'POST v2/teaching-assistant/groups/3/drafts': {
              'error': false,
              'data': bad,
            },
          },
        );
        await expectLater(
          repository(api).generate(TeachingGenerationIntent(3, request())),
          throwsA(isA<ApiException>()),
        );
        expect(api.requests.single.queryParameters, isEmpty);
        final body = api.requests.single.data;
        expect(
          (body is String ? jsonDecode(body) : body)['operation_id'],
          teachingOperation,
        );
      }
    },
  );
  test(
    'uncertain generation recovery reads its UUID without another compute',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/teaching-assistant/operations/$teachingOperation': {
            'error': false,
            'data': teachingDetailJson(),
          },
        },
      );
      expect(
        (await repository(api).receipt(teachingOperation)).id,
        teachingDraftId,
      );
      expect(api.requests.single.method, 'GET');
      expect(api.requestedPaths, [
        'GET v2/teaching-assistant/operations/$teachingOperation',
      ]);
    },
  );
  test('late response after context replacement is discarded', () async {
    var active = true;
    final response = Completer<Map<String, dynamic>>();
    final client = _DelayedTeachingClient(response);
    final repo = ApiTeachingRepository(
      client,
      schoolId: 1,
      readerId: 7,
      contextActive: () => active,
    );
    final pending = repo.configuration();
    active = false;
    response.complete({'data': teachingConfigurationJson()});
    await expectLater(pending, throwsA(isA<ApiException>()));
  });
  test(
    'independent insights use only the insight purpose and exact canonical task',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/test-insights/configuration': {
            'error': false,
            'data': teachingConfigurationJson(),
          },
        },
      );
      expect(
        (await repository(api).configuration(insights: true)).writable,
        isTrue,
      );
      expect(api.requestedPaths, ['GET v2/test-insights/configuration']);
    },
  );
}

class _DelayedTeachingClient extends ApiClient {
  _DelayedTeachingClient(this.response)
    : super(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
      );
  final Completer<Map<String, dynamic>> response;
  @override
  Future<Map<String, dynamic>?> send({
    required String method,
    required String url,
    Object? data,
    Map<String, dynamic>? queryParameters,
    int? schoolId,
    int? accountId,
    bool useAuthToken = true,
    bool notFoundAsNull = false,
    String? idempotencyKey,
    bool privateRequest = false,
    Duration? requestTimeout,
  }) => response.future;
}
