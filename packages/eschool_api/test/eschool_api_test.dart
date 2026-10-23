import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

/// Answers every request with one status and JSON body, and keeps the request
/// so a test can look at what was sent.
class _FakeServer implements HttpClientAdapter {
  _FakeServer(this.status, this.body);

  final int status;
  final String body;
  RequestOptions? request;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    request = options;
    return ResponseBody.fromString(
      body,
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

Map<String, dynamic> _json(String source) =>
    jsonDecode(source) as Map<String, dynamic>;

void main() {
  // The same payload the admin panel's Contracts suite checks against
  // openapi.yaml, so the server and this client read one shape.
  final contextJson = File(
    'test/fixtures/me_context_parent.json',
  ).readAsStringSync();

  group('ContextResponse', () {
    test('reads a parent\'s context with their children', () {
      final response = ContextResponse.fromJson(_json(contextJson));
      final context = response.data;

      expect(response.error, isFalse);
      expect(response.code, 200);
      expect(context.user.name, 'Farid Parent');
      expect(context.user.image, isNull);
      expect(context.user.roles, ['Parent']);
      expect(context.schools.single.school.shortName, 'NHS');
      expect(context.schools.single.roles, ['Parent']);
      expect(context.currentSchool.timezone, 'Africa/Johannesburg');
      expect(context.currentSchool.branding.themeColor, '#22577A');
      expect(context.currentSchool.branding.secondaryColor, isNull);
      final year = context.currentSchool.sessionYear!;
      expect(year.startDate, DateTime(2026, 1, 14));
      expect(year.terms.singleWhere((term) => term.current).name, 'Term 4');
      expect(context.modules, isEmpty);
      expect(context.featureFlags, isEmpty);
      expect(context.signInMethods, ['password']);
      expect(context.children.map((child) => child.id), [21, 23]);
      expect(context.children.first.school.crestUrl, endsWith('crest.png'));
    });

    test('ignores fields added to the API after it was generated', () {
      final json = _json(contextJson);
      json['added_later'] = true;
      (json['data'] as Map<String, dynamic>)['added_later'] = {'any': 'thing'};

      expect(ContextResponse.fromJson(json).data.children, hasLength(2));
    });

    test('reads a school with no current year', () {
      final json = _json(contextJson);
      ((json['data'] as Map<String, dynamic>)['current_school']
              as Map<String, dynamic>)['session_year'] =
          null;

      expect(
        ContextResponse.fromJson(json).data.currentSchool.sessionYear,
        isNull,
      );
    });
  });

  group('ErrorResponse', () {
    test('reads every validation message by field', () {
      final error = ErrorResponse.fromJson({
        'error': true,
        'message': 'The name field is required.',
        'data': {
          'errors': {
            'name': ['The name field is required.'],
            'items.0.amount': ['The amount must be an integer.'],
          },
        },
        'code': 'validation_failed',
      });

      expect(error.code, ErrorCode.validationFailed);
      expect(error.data!.errors['items.0.amount'], [
        'The amount must be an integer.',
      ]);
    });

    test('accepts an error code it does not know yet', () {
      final error = ErrorResponse.fromJson({
        'error': true,
        'message': 'Something new.',
        'data': null,
        'code': 'added_in_a_later_phase',
      });

      expect(error.code, ErrorCode.unknownDefaultOpenApi);
      expect(error.data, isNull);
    });
  });

  group('PlatformApi.getMeContext', () {
    test('asks for /me/context with the bearer token', () async {
      final server = _FakeServer(200, contextJson);
      final api = EschoolApi(
        basePathOverride: 'https://admin.example.com/api/v2',
      );
      api.dio.httpClientAdapter = server;
      api.setBearerAuth('bearerAuth', '12|secret');

      final response = await api.getPlatformApi().getMeContext();

      expect(server.request!.method, 'GET');
      expect(
        server.request!.uri.toString(),
        'https://admin.example.com/api/v2/me/context',
      );
      expect(server.request!.headers['Authorization'], 'Bearer 12|secret');
      expect(response.data!.data.children, hasLength(2));
    });

    test(
      'a refused token comes back as a 401 with the error envelope',
      () async {
        final server = _FakeServer(
          401,
          jsonEncode({
            'error': true,
            'message': 'Sign in again to continue.',
            'data': null,
            'code': 'unauthenticated',
          }),
        );
        final api = EschoolApi(
          basePathOverride: 'https://admin.example.com/api/v2',
        );
        api.dio.httpClientAdapter = server;

        await expectLater(
          api.getPlatformApi().getMeContext(),
          throwsA(
            isA<DioException>()
                .having((e) => e.response?.statusCode, 'status', 401)
                .having(
                  (e) => ErrorResponse.fromJson(
                    e.response!.data as Map<String, dynamic>,
                  ).code,
                  'code',
                  ErrorCode.unauthenticated,
                ),
          ),
        );
      },
    );
  });

  group('Academic references', () {
    test(
      'an explicit clear reaches the API as null with school and retry headers',
      () async {
        final server = _FakeServer(
          200,
          jsonEncode({
            'error': false,
            'message': 'Saved',
            'code': 200,
            'data': {
              'class_id': 21,
              'grade_level_id': null,
              'grade_level': null,
            },
          }),
        );
        final api = EschoolApi(
          basePathOverride: 'https://admin.example.com/api/v2',
        );
        api.dio.httpClientAdapter = server;
        final answer = await api.getAcademicsApi().updateClassGradeLevel(
          class_: 21,
          xSchoolId: 2,
          idempotencyKey: 'clear-grade',
          classGradeWrite: ClassGradeWrite(gradeLevelId: null),
        );
        expect(server.request!.path, '/academics/classes/21/grade-level');
        expect(server.request!.method, 'PUT');
        expect(server.request!.headers['X-School-Id'], 2);
        expect(server.request!.headers['Idempotency-Key'], 'clear-grade');
        expect(jsonDecode(server.request!.data as String), {
          'grade_level_id': null,
        });
        expect(answer.data!.data.gradeLevelId, isNull);
        expect(answer.data!.data.gradeLevel, isNull);
      },
    );

    test('reference thresholds remain integers and Grade R remains a code', () {
      final reference = AcademicReference.fromJson({
        'grades': [
          {
            'id': 1,
            'code': 'R',
            'name': 'Grade R',
            'phase': 'Foundation',
            'sort_order': 0,
          },
        ],
        'rating_scale': [
          {
            'level': 7,
            'name': 'Outstanding achievement',
            'min_basis_points': 8000,
            'max_basis_points': 10000,
          },
        ],
      });
      expect(reference.grades.single.code, 'R');
      expect(reference.ratingScale.single.minBasisPoints, 8000);
      expect(reference.ratingScale.single.maxBasisPoints, 10000);
    });

    test(
      'a mapped grade decodes without depending on legacy class payloads',
      () {
        final mapping = ClassGradeMapping.fromJson({
          'class_id': 21,
          'grade_level_id': 8,
          'grade_level': {
            'id': 8,
            'code': '7',
            'name': 'Grade 7',
            'phase': 'Senior',
            'sort_order': 7,
          },
        });
        expect(mapping.gradeLevelId, 8);
        expect(mapping.gradeLevel!.code, '7');
        expect(mapping.toJson()['grade_level_id'], 8);
      },
    );
  });
}
