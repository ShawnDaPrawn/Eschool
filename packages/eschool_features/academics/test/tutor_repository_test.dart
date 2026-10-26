import 'dart:typed_data';
import 'package:crypto/crypto.dart';
import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'tutor_fixture.dart';

ApiClient client(FakeApi api) => ApiClient(
  authToken: () => 'synthetic-token',
  onUnauthorized: () {},
  sendsBearerToken: false,
  createDio: () => Dio()..httpClientAdapter = api,
);
void main() {
  test(
    'Parent subject choices bind the exact cookie account and child',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/academics/content/learners/9': {
            'error': false,
            'data': {'learner_id': 9, 'page': 1, 'last_page': 1, 'items': []},
          },
        },
      );
      final repo = ApiTutorSummaryRepository(
        client(api),
        schoolId: 1,
        readerId: 7,
        contextActive: () => true,
      );
      expect((await repo.subjects(9)).items, isEmpty);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '7');
      expect(api.requestedPaths, ['GET v2/academics/content/learners/9']);
    },
  );
  test(
    'cookie request pins school and actor; replacement projection is refused',
    () async {
      for (final wrong in [false, true]) {
        final api = FakeApi(
          routes: {
            'GET v2/tutor/configuration': {
              'error': false,
              'data': {...tutorConfigurationJson(), if (wrong) 'reader_id': 99},
            },
          },
        );
        final repo = ApiTutorRepository(
          client(api),
          schoolId: 1,
          readerId: 7,
          contextActive: () => true,
        );
        if (wrong) {
          await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
        } else {
          expect((await repo.configuration()).learnerId, 9);
        }
        expect(api.requests.single.headers['X-School-Id'], '1');
        expect(api.requests.single.headers['X-Account-Id'], '7');
        expect(
          api.requests.single.headers.containsKey('Authorization'),
          isFalse,
        );
      }
    },
  );
  test('immutable turn retries preserve UUID and exact body', () async {
    final api = FakeApi(
      routes: {
        'POST v2/tutor/sessions/$tutorSession/messages': {
          'error': false,
          'data': {'school_id': 1, 'reader_id': 7, ...tutorTurnJson()},
        },
      },
    );
    final repo = ApiTutorRepository(
      client(api),
      schoolId: 1,
      readerId: 7,
      contextActive: () => true,
    );
    final intent = TutorTurnIntent(
      tutorSession,
      TutorTurnWrite(
        operationId: tutorOperation,
        question: 'Private canary',
        working: 'two parts',
      ),
    );
    intent.body['question'] = 'mutated copy';
    await repo.send(intent);
    await repo.send(intent);
    expect(
      api.requests.first.data is String
          ? jsonDecode(api.requests.first.data as String)
          : api.requests.first.data,
      api.requests.last.data is String
          ? jsonDecode(api.requests.last.data as String)
          : api.requests.last.data,
    );
    expect(
      (api.requests.last.data is String
          ? jsonDecode(api.requests.last.data as String)
          : api.requests.last.data)['question'],
      'Private canary',
    );
  });
  test('late response after context withdrawal is discarded', () async {
    var active = true;
    final api = FakeApi(
      routes: {
        'GET v2/tutor/configuration': (RequestOptions _) {
          active = false;
          return {'error': false, 'data': tutorConfigurationJson()};
        },
      },
    );
    final repo = ApiTutorRepository(
      client(api),
      schoolId: 1,
      readerId: 7,
      contextActive: () => active,
    );
    await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
  });
  test('quarantined nested input is refused before rendering', () async {
    final detail = FakeTutorRepository()
      ..turns = [
        {...tutorTurnJson(state: 'quarantined'), 'question': 'unsafe canary'},
      ];
    final api = FakeApi(
      routes: {
        'GET v2/tutor/sessions/$tutorSession': {
          'error': false,
          'data': detail.detail().toJson(),
        },
      },
    );
    final repo = ApiTutorRepository(
      client(api),
      schoolId: 1,
      readerId: 7,
      contextActive: () => true,
    );
    await expectLater(repo.read(tutorSession), throwsA(isA<ApiException>()));
  });
  test(
    'Parent summary calls only its scoped aggregate and rejects raw chat',
    () async {
      for (final raw in [false, true]) {
        final api = FakeApi(
          routes: {
            'GET v2/tutor/parent/learners/9/subjects/4/summary': {
              'error': false,
              'data': {
                'school_id': 1,
                'reader_id': 7,
                'learner_id': 9,
                'subject_id': 4,
                'subject_name': 'Mathematics',
                'learning_sessions': 2,
                'learning_steps': 3,
                'suggestion_key':
                    'Ask your child to explain one worked example.',
                'contains_raw_chat': raw,
              },
            },
          },
        );
        final repo = ApiTutorSummaryRepository(
          client(api),
          schoolId: 1,
          readerId: 7,
          contextActive: () => true,
        );
        if (raw) {
          await expectLater(repo.summary(9, 4), throwsA(isA<ApiException>()));
        } else {
          expect((await repo.summary(9, 4)).learningSteps, 3);
        }
        expect(api.requestedPaths, [
          'GET v2/tutor/parent/learners/9/subjects/4/summary',
        ]);
      }
    },
  );
  test(
    'approved photo previews accept the bounded one-MiB derivative and still reject excess bytes',
    () async {
      for (final length in [684441, 1048577]) {
        final bytes = Uint8List(length);
        final reference = TutorPhotoReceipt.fromJson({
          'school_id': 1,
          'reader_id': 7,
          'id': tutorPhoto,
          'operation_id': tutorOperation,
          'mime': 'image/png',
          'sha256': sha256.convert(bytes).toString(),
        });
        final api = FakeApi(
          routes: {
            'GET v2/tutor/sessions/$tutorSession/photos/$tutorPhoto':
                FakeBinaryResponse(bytes, contentType: 'image/png'),
          },
        );
        final repo = ApiTutorRepository(
          client(api),
          schoolId: 1,
          readerId: 7,
          contextActive: () => true,
        );
        if (length <= 1048576) {
          expect(await repo.preview(tutorSession, reference), bytes);
        } else {
          await expectLater(
            repo.preview(tutorSession, reference),
            throwsA(isA<ApiException>()),
          );
        }
        expect(api.requests.single.headers['X-Account-Id'], '7');
        expect(api.requests.single.headers['X-School-Id'], '1');
      }
    },
  );
}
