import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  ApiReportRepository repository(FakeApi api) => ApiReportRepository(
    ApiClient(
      authToken: () => 'synthetic-token',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
  );
  final base = '${AppConfig.apiV2Url}academics/reports/';
  Object ok(Object data) => {'error': false, 'data': data};
  test(
    'every report read and mutation is pinned to the school and operation key',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/academics/reports/configuration': ok(
            reportConfigurationFixture(),
          ),
          'POST v2/academics/reports/cards/9/comment': ok({
            'id': 1,
            'revision': 1,
            'batch_revision': 2,
            'fingerprint': 'a' * 64,
            'text': 'Reviewed comment',
          }),
        },
      );
      final repo = repository(api);
      final config = await repo.configuration(year: 1);
      expect(config.subjects.single.name, 'Mathematics');
      await repo.comment(
        9,
        AcademicReportCommentWrite(
          subjectId: 2,
          text: 'Reviewed comment',
          revision: 0,
          batchRevision: 1,
        ),
        'same-operation',
      );
      expect(
        api.requests.every((r) => r.headers['X-School-Id'] == '1'),
        isTrue,
      );
      expect(api.requests.last.headers['Idempotency-Key'], 'same-operation');
      expect(api.requests.last.data['revision'], 0);
      expect(api.requests.first.queryParameters['session_year_id'], 1);
    },
  );
  test(
    'family PDF refreshes access, preserves signature query and reads bytes without redirect',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/academics/reports/cards/9/access': ok({
            'id': 9,
            'learner_id': 11,
            'download_url': reportDownloadFixture(AppConfig.apiV2Url),
          }),
          'GET v2/academics/reports/cards/9/download': FakeBinaryResponse(
            base64Decode(reportSmokePdfBase64),
          ),
        },
      );
      final bytes = await repository(api).familyPdf(9, 11);
      expect(ascii.decode(bytes.take(5).toList()), '%PDF-');
      final request = api.requests.last;
      expect(request.followRedirects, isFalse);
      expect(request.responseType, ResponseType.bytes);
      expect(request.uri.queryParameters['staff'], '0');
      expect(request.uri.queryParameters['signature'], 'a' * 64);
      expect(request.headers['Authorization'], 'Bearer synthetic-token');
      expect(api.requestedPaths, [
        'GET v2/academics/reports/cards/9/access',
        'GET v2/academics/reports/cards/9/download',
      ]);
    },
  );
  test('mismatched child never starts a binary download', () async {
    final api = FakeApi(
      routes: {
        'GET v2/academics/reports/cards/9/access': ok({
          'id': 9,
          'learner_id': 12,
          'download_url': reportDownloadFixture(AppConfig.apiV2Url),
        }),
      },
    );
    await expectLater(
      repository(api).familyPdf(9, 11),
      throwsA(isA<ApiException>()),
    );
    expect(api.requests, hasLength(1));
  });
  for (final change in <String Function(String)>[
    (s) => s.replaceFirst(Uri.parse(base).host, 'foreign.example'),
    (s) => s.replaceFirst('/cards/9/', '/cards/10/'),
    (s) => s.replaceFirst('school=1', 'school=2'),
    (s) => s.replaceFirst('staff=0', 'staff=1'),
    (s) => '$s&school=1',
    (s) => '$s#fragment',
    (s) => s.replaceFirst('reader=21', 'reader=zero'),
    (s) => s.replaceFirst('signature=', 'signature=invalid'),
    (s) => '/api/v2/academics/reports/cards/9/download',
    (s) => s.replaceFirst('expires=1999999999', 'expires=soon'),
  ]) {
    test(
      'untrusted PDF metadata is refused before any authenticated download ${change(reportDownloadFixture(AppConfig.apiV2Url))}',
      () {
        expect(
          () => validatedReportDownload(
            base: base,
            download: change(reportDownloadFixture(AppConfig.apiV2Url)),
            school: 1,
            card: 9,
            staff: false,
          ),
          throwsA(isA<ApiException>()),
        );
      },
    );
  }
  test(
    'wrong bytes fail instead of opening HTML or JSON as a report',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/academics/reports/cards/9/access': ok({
            'id': 9,
            'learner_id': 11,
            'download_url': reportDownloadFixture(AppConfig.apiV2Url),
          }),
          'GET v2/academics/reports/cards/9/download': const FakeBinaryResponse(
            [60, 104, 116, 109, 108, 62],
          ),
        },
      );
      await expectLater(
        repository(api).familyPdf(9, 11),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'all roster/batch/comment/history pages retain scope and page parameters',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/academics/reports/learners/11': ok(
            reportFamilyFixture(AppConfig.apiV2Url, page: 2),
          ),
          'GET v2/academics/reports/comments': ok({
            'session_year_id': 1,
            'page': 2,
            'last_page': 2,
            'rows': [reportCommentFixture()],
          }),
        },
      );
      final repo = repository(api);
      expect((await repo.family(11, page: 2)).page, 2);
      expect((await repo.comments(1, page: 2)).rows.single.learnerId, 11);
      for (final request in api.requests) {
        expect(request.queryParameters['page'], 2);
        expect(request.headers['X-School-Id'], '1');
      }
    },
  );
}
