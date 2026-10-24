import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Object ok(Object value) => {'error': false, 'data': value};
  ApiAcademicContentRepository repository(
    FakeApi api, {
    bool Function()? active,
    String Function()? token,
  }) => ApiAcademicContentRepository(
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
    'source upload pins school and immutable selection, draft writes retain revision and UUID',
    () async {
      final writes = <RequestOptions>[];
      final api = FakeApi(
        routes: {
          'POST v2/academics/content/items': (RequestOptions request) {
            writes.add(request);
            return ok({
              'id': 10,
              'version_id': 11,
              'item_revision': 2,
              'version_revision': 2,
            });
          },
          'PUT v2/academics/content/versions/11': (RequestOptions request) {
            writes.add(request);
            return ok({
              'id': 10,
              'version_id': 11,
              'item_revision': 3,
              'version_revision': 3,
            });
          },
        },
      );
      final repo = repository(api),
          selection = ContentSelection.fromJson(contentSelectionFixture());
      final bytes = Uint8List.fromList([65, 66]);
      final upload = ContentUpload('../lesson.md', bytes);
      bytes[0] = 0;
      expect(upload.bytes.first, 65);
      await repo.create(selection, upload, 'original-operation');
      final body = writes.first.data as FormData;
      expect(body.files.single.key, 'source');
      expect(body.files.single.value.filename!.contains('/'), isFalse);
      expect(
        body.fields.singleWhere((f) => f.key == 'operation_id').value,
        'original-operation',
      );
      await repo.save(
        11,
        ContentDraftWrite(
          operationId: 'save-operation',
          itemRevision: 2,
          versionRevision: 2,
          selection: selection,
          markdown: '# Reviewed',
        ),
      );
      expect(writes.every((r) => r.headers['X-School-Id'] == '1'), isTrue);
      expect(writes.last.data['operation_id'], 'save-operation');
      expect(writes.last.data['item_revision'], 2);
    },
  );
  for (final field in [
    'learner_id',
    'school_id',
    'reader_id',
    'body_sha256',
    'offline_until',
  ]) {
    test(
      'reader rejects mismatched $field before returning private material',
      () async {
        final data = contentReaderFixture();
        data[field] = field == 'body_sha256'
            ? '0' * 64
            : field == 'offline_until'
            ? '2026-10-26T09:00:00Z'
            : 999;
        final api = FakeApi(
          routes: {
            'GET v2/academics/content/learners/11/versions/11': ok(data),
          },
        );
        await expectLater(
          repository(api).reader(11, 11),
          throwsA(isA<ApiException>()),
        );
      },
    );
  }
  test(
    'identity change prevents any request with the old repository',
    () async {
      var token = 'first', active = true;
      final api = FakeApi(routes: {});
      final pinned = repository(api, active: () => active, token: () => token);
      token = 'second';
      await expectLater(pinned.library(11), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
      token = 'first';
      active = false;
      await expectLater(pinned.library(11), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test('Markdown enforces UTF8 byte budget before transport', () async {
    final api = FakeApi(routes: {});
    await expectLater(
      repository(api).save(
        11,
        ContentDraftWrite(
          operationId: 'save',
          itemRevision: 1,
          versionRevision: 1,
          selection: ContentSelection.fromJson(contentSelectionFixture()),
          markdown: 'é' * (1024 * 1024 + 1),
        ),
      ),
      throwsA(isA<ApiException>()),
    );
    expect(api.requests, isEmpty);
  });
  test('notification target requires both canonical positive identifiers', () {
    expect(contentLinkArguments({}, {}), isNull);
    final target = contentLinkArguments({}, {
      'learner': '11',
      'version': '12',
    })!;
    expect(target.learner, 11);
    expect(target.version, 12);
    for (final query in [
      {'learner': '11'},
      {'learner': '0', 'version': '1'},
      {'learner': '12', 'version': '../2'},
    ]) {
      expect(() => contentLinkArguments({}, query), throwsFormatException);
    }
  });
}
