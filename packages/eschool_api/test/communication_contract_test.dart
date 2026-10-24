import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  const operation = 'd2160bf5-7a51-4e32-8910-f978744cc17d';
  test(
    'family configuration contains current identity without staff contact scopes',
    () {
      final config = CommunicationConfiguration.fromJson({
        'school_id': 1,
        'reader_id': 12,
        'timezone': 'Africa/Johannesburg',
        'staff_available': false,
        'can_manage': false,
        'session_year_id': null,
        'years': [],
        'scopes': [],
        'audiences': [],
        'rules': null,
        'channels': ['in_app', 'push', 'email'],
      });
      expect(config.rules, isNull);
      expect(config.staffAvailable, isFalse);
      expect(config.readerId, 12);
      expect(config.scopes, isEmpty);
    },
  );
  test(
    'a private draft retains explicit guardian scope, schedule, channels and operation',
    () {
      final write = CommunicationMessageWrite.fromJson({
        'operation_id': operation,
        'revision': 2,
        'title': 'Private reminder',
        'markdown': '# Read this',
        'channels': ['in_app', 'email'],
        'scheduled_local': '2026-10-28T10:30',
        'audience': {
          'kind': 'group',
          'session_year_id': 3,
          'target_id': 9,
          'parents': true,
        },
      });
      final json = write.toJson();
      expect(json['audience'], {
        'kind': 'group',
        'session_year_id': 3,
        'target_id': 9,
        'parents': true,
      });
      expect(json['operation_id'], operation);
      expect(json['scheduled_local'], '2026-10-28T10:30');
      expect(json['channels'], ['in_app', 'email']);
      expect(json.containsKey('school_id'), isFalse);
    },
  );
  test(
    'the published reader omits audience identities and private recipient statistics',
    () {
      final reader = CommunicationMessageReader.fromJson({
        'id': 4,
        'title': 'A school message',
        'state': 'sent',
        'revision': 4,
        'published_at': '2026-10-24T09:00:00Z',
        'scheduled_at': null,
        'updated_at': '2026-10-24T09:01:00Z',
        'school_id': 1,
        'reader_id': 12,
        'markdown': 'Private family text',
        'read_at': null,
      });
      expect(reader.schoolId, 1);
      expect(reader.readAt, isNull);
      expect(reader.toJson().containsKey('audience'), isFalse);
      expect(reader.toJson().containsKey('read_count'), isFalse);
    },
  );
  test(
    'approval transport pins school, message, compared fingerprint and stable operation',
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
                  'message': 'Saved',
                  'code': 200,
                  'data': {'id': 4, 'revision': 4, 'state': 'approved'},
                },
              ),
            );
          },
        ),
      );
      final result = await CommunicationApi(dio).decideSchoolMessage(
        message: 4,
        xSchoolId: 1,
        communicationDecisionWrite: CommunicationDecisionWrite.fromJson({
          'operation_id': operation,
          'revision': 3,
          'fingerprint': 'a' * 64,
          'decision': 'approve',
          'reason': 'Reviewed current audience and body',
        }),
      );
      expect(sent!.path, '/communication/drafts/4/decision');
      expect(sent!.headers['X-School-Id'], 1);
      final data = sent!.data is String
          ? jsonDecode(sent!.data as String)
          : sent!.data;
      expect(data['operation_id'], operation);
      expect(data['revision'], 3);
      expect(data['fingerprint'], 'a' * 64);
      expect(result.data!.data.revision, 4);
    },
  );
}
