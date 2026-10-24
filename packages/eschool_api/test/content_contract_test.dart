import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  final selection = ContentSelection(
    subjectId: 4,
    gradeLevelId: 8,
    sessionYearId: 2,
    title: 'Fractions',
    summary: '',
    groupIds: {7, 9},
    topicIds: {3},
  );
  const operation = 'd2160bf5-7a51-4e32-8910-f978744cc17d';
  test(
    'multipart upload pins school, private source and reviewed selection',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      RequestOptions? sent;
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            sent = options;
            handler.resolve(
              Response(
                requestOptions: options,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'Queued',
                  'code': 200,
                  'data': {
                    'id': 10,
                    'version_id': 11,
                    'item_revision': 1,
                    'version_revision': 1,
                  },
                },
              ),
            );
          },
        ),
      );
      final response = await AcademicsApi(dio).uploadAcademicContent(
        selection: jsonEncode(selection.toJson()),
        source_: MultipartFile.fromString('# Fractions', filename: 'lesson.md'),
        operationId: operation,
        xSchoolId: 5,
      );
      expect(sent!.path, '/academics/content/items');
      expect(sent!.headers['X-School-Id'], 5);
      final body = sent!.data as FormData;
      expect(
        body.fields.singleWhere((e) => e.key == 'operation_id').value,
        operation,
      );
      expect(
        jsonDecode(
          body.fields.singleWhere((e) => e.key == 'selection').value,
        )['group_ids'],
        [7, 9],
      );
      expect(body.files.single.key, 'source');
      expect(body.files.single.value.filename, 'lesson.md');
      expect(response.data!.data.versionId, 11);
    },
  );
  test('publication preserves boolean human review and conflict revisions', () {
    final input = ContentPublishWrite(
      operationId: operation,
      itemRevision: 3,
      versionRevision: 4,
      fingerprint: 'a' * 64,
      reviewed: true,
      reason: 'Leadership reviewed the audience',
    );
    final json = input.toJson();
    expect(json['reviewed'], isTrue);
    expect(json['item_revision'], 3);
    expect(ContentPublishWrite.fromJson(json).operationId, operation);
    expect(ContentPublishWrite.fromJson(json).reviewed, isTrue);
  });
  test(
    'reader retains account, school, learner, checksum and lease boundaries',
    () {
      final reader = ContentReader.fromJson({
        'id': 11,
        'content_item_id': 10,
        'learner_id': 7,
        'school_id': 5,
        'reader_id': 12,
        'title': 'Fractions',
        'summary': '',
        'markdown': '# Fractions',
        'body_sha256': 'a' * 64,
        'published_at': '2026-10-24T08:00:00Z',
        'topics': [
          {'id': 3, 'name': 'Equivalent fractions'},
        ],
        'server_time': '2026-10-24T09:00:00Z',
        'offline_until': '2026-10-25T09:00:00Z',
      });
      expect(reader.readerId, 12);
      expect(reader.schoolId, 5);
      expect(reader.learnerId, 7);
      expect(
        reader.offlineUntil.difference(reader.serverTime),
        const Duration(days: 1),
      );
      expect(reader.toJson().containsKey('source_url'), isFalse);
      expect(ContentReader.fromJson(reader.toJson()).bodySha256, 'a' * 64);
    },
  );
  test(
    'queued version has nullable draft body and never invents a publication',
    () {
      final row = ContentItem.fromJson({
        'id': 10,
        'can_write': true,
        'active': true,
        'revision': 1,
        'author_id': 12,
        'draft': {
          'id': 11,
          'content_item_id': 10,
          'number': 1,
          'state': 'queued',
          'selection': selection.toJson(),
          'markdown': null,
          'revision': 1,
          'failure_code': null,
          'duration_seconds': null,
          'language': null,
          'created_at': '2026-10-24T08:00:00Z',
          'published_at': null,
          'source': {
            'name': 'lesson.md',
            'extension': 'md',
            'mime': 'text/plain',
            'bytes': 100,
            'scan_state': 'pending',
            'scanned_at': null,
          },
        },
        'published': null,
        'page': 1,
        'last_page': 1,
        'versions': [],
      });
      expect(row.draft!.markdown, isNull);
      expect(row.published, isNull);
      expect(row.toJson()['published'], isNull);
      expect(ContentItem.fromJson(row.toJson()).draft!.selection.groupIds, {
        7,
        9,
      });
    },
  );
}
