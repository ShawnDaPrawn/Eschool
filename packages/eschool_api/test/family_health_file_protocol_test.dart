import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

class _Adapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) async {
    requests.add(options);
    await stream?.drain<void>();
    if (options.method == 'GET') {
      return ResponseBody.fromBytes(
        [37, 80, 68, 70],
        200,
        headers: {
          Headers.contentTypeHeader: ['application/pdf'],
        },
      );
    }
    return ResponseBody.fromString(
      jsonEncode({
        'error': false,
        'message': 'OK',
        'code': 200,
        'data': {
          'person_id': 12,
          'profile_type': 'learner',
          'available_profiles': ['learner'],
          'categories': <Object>[],
        },
      }),
      200,
      headers: {
        Headers.contentTypeHeader: ['application/json'],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  test(
    'generated health download is binary and retains expected school/account headers',
    () async {
      final adapter = _Adapter();
      final api = FamilyFormsApi(Dio()..httpClientAdapter = adapter);
      final result = await api.getFamilyHealthFile(
        learner: 11,
        file: '8aef50e7-2429-4d2c-a523-668ae436c4d4.pdf',
        xSchoolId: 1,
        xAccountId: 7,
      );
      expect(result.data, [37, 80, 68, 70]);
      expect(
        adapter.requests.single.path,
        '/family-forms/learners/11/health/files/8aef50e7-2429-4d2c-a523-668ae436c4d4.pdf',
      );
      expect(adapter.requests.single.responseType, ResponseType.bytes);
      expect(adapter.requests.single.headers['X-School-Id'], 1);
      expect(adapter.requests.single.headers['X-Account-Id'], 7);
    },
  );
  test(
    'generated health upload fixes its purpose to one learner/field and returns typed health schema',
    () async {
      final adapter = _Adapter();
      final api = FamilyFormsApi(Dio()..httpClientAdapter = adapter);
      final result = await api.uploadFamilyHealthFile(
        learner: 11,
        field: 'health_document',
        document: MultipartFile.fromBytes([
          37,
          80,
          68,
          70,
        ], filename: 'synthetic.pdf'),
        xSchoolId: 1,
        xAccountId: 7,
      );
      expect(result.data!.data.personId, 12);
      expect(
        adapter.requests.single.path,
        '/family-forms/learners/11/health/fields/health_document/file',
      );
      final form = adapter.requests.single.data as FormData;
      expect(form.fields, isEmpty);
      expect(form.files.single.key, 'document');
      expect(adapter.requests.single.headers['X-School-Id'], 1);
      expect(adapter.requests.single.headers['X-Account-Id'], 7);
    },
  );
}
