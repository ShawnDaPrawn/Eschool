import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

class ScriptedAdapter implements HttpClientAdapter {
  final List<RequestOptions> requests = [];
  int status = 200;
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? body,
    Future<void>? cancel,
  ) async {
    requests.add(options);
    Object data;
    if (status != 200) {
      data = {
        'error': true,
        'message': 'A mark changed. Reload before saving.',
        'code': 'conflict',
        'data': null,
      };
    } else if (options.path.endsWith('teaching-groups')) {
      data = ok({
        'groups': [
          {
            'id': 1,
            'name': 'Maths 7A',
            'subject_id': 1,
            'grade_level_id': 8,
            'session_year_id': 2,
            'class_section_id': null,
            'active': true,
            'revision': 1,
            'year_name': '2027',
          },
        ],
        'current_page': 1,
        'last_page': 1,
        'total': 1,
      });
    } else if (options.path.endsWith('reference')) {
      data = ok({
        'grades': [
          {
            'id': 8,
            'code': '7',
            'name': 'Grade 7',
            'phase': 'Senior',
            'sort_order': 7,
          },
        ],
        'rating_scale': [],
      });
    } else {
      data = ok({
        'session_year_id': 2,
        'terms': [
          {
            'id': 3,
            'session_year_id': 2,
            'name': 'Term 1',
            'starts_on': '2027-01-01',
            'ends_on': '2027-03-31',
            'grade_from': 0,
            'grade_to': 12,
            'marks_lock_on': null,
            'reports_on': null,
            'current': false,
            'needs_review': false,
          },
        ],
      });
    }
    return ResponseBody.fromString(
      jsonEncode(data),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  Map<String, Object?> ok(Object data) => {
    'error': false,
    'message': 'Fetched',
    'code': 200,
    'data': data,
  };
  @override
  void close({bool force = false}) {}
}

void main() {
  test(
    'group and grade-term requests retain explicit school scope and human year labels',
    () async {
      final adapter = ScriptedAdapter();
      final client = ApiClient(
        authToken: () => 'test-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = adapter,
      );
      final repository = ApiMarksRepository(client, schoolId: 7);
      final groups = await repository.groups();
      expect(groups.single.yearName, '2027');
      final terms = await repository.terms(groups.single);
      expect(terms.single.id, 3);
      expect(adapter.requests.last.queryParameters['session_year_id'], 2);
      expect(
        adapter.requests.last.queryParameters['grade'],
        7,
      ); // Reference ordinal, not grade-level id8.
      for (final request in adapter.requests) {
        expect(request.headers['X-School-Id'], '7');
        expect(request.headers['Authorization'], 'Bearer test-token');
      }
    },
  );
  test(
    'the real API client retains a v2 conflict status for safe draft handling',
    () async {
      final adapter = ScriptedAdapter()..status = 409;
      final client = ApiClient(
        authToken: () => 'test-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = adapter,
      );
      final repository = ApiMarksRepository(client, schoolId: 7);
      await expectLater(
        repository.matrix(1, 3),
        throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 409)),
      );
    },
  );
}
