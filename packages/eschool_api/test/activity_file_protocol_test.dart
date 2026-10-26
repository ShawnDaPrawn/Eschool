import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const attachmentId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
Map<String, dynamic> metadata() => {
  'id': attachmentId,
  'activity_group_id': 3,
  'material_id': 9,
  'name': 'Synthetic worksheet.pdf',
  'mime': 'application/pdf',
  'bytes': 4,
  'revision': 1,
  'removed': false,
};

class _Files implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    await stream?.drain<void>();
    if (options.path.endsWith('/content')) {
      return ResponseBody.fromBytes(
        [37, 80, 68, 70],
        200,
        headers: {
          Headers.contentTypeHeader: ['application/pdf'],
        },
      );
    }
    final removed = options.path.endsWith('/remove');
    return ResponseBody.fromString(
      jsonEncode({
        'error': false,
        'message': 'OK',
        'code': 200,
        'data': options.method == 'GET'
            ? {
                'items': [metadata()],
                'material_revision': 2,
                'can_upload': false,
                'can_remove': false,
              }
            : {
                ...metadata(),
                'removed': removed,
                'revision': removed ? 2 : 1,
                'material_revision': removed ? 3 : 2,
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
    'typed list preserves denied capabilities and binds school/account',
    () async {
      final adapter = _Files();
      final result = await ActivitiesApi(Dio()..httpClientAdapter = adapter)
          .listActivityMaterialFiles(
            group: 3,
            material: 9,
            xSchoolId: 1,
            xAccountId: 7,
          );
      expect(result.data!.data.canUpload, isFalse);
      expect(result.data!.data.canRemove, isFalse);
      expect(result.data!.data.items.single.id, attachmentId);
      expect(result.data!.data.items.single.mime.value, 'application/pdf');
      expect(result.data!.data.toJson()['can_upload'], isFalse);
      expect(adapter.requests.single.headers['X-School-Id'], 1);
      expect(adapter.requests.single.headers['X-Account-Id'], 7);
    },
  );
  test(
    'generated binary download uses the scoped content path and bytes response type',
    () async {
      final adapter = _Files();
      final result = await ActivitiesApi(Dio()..httpClientAdapter = adapter)
          .getActivityMaterialFile(
            group: 3,
            material: 9,
            file: attachmentId,
            xSchoolId: 1,
            xAccountId: 7,
          );
      expect(result.data, [37, 80, 68, 70]);
      expect(
        adapter.requests.single.path,
        '/activities/groups/3/materials/9/files/$attachmentId/content',
      );
      expect(adapter.requests.single.responseType, ResponseType.bytes);
      expect(adapter.requests.single.headers['X-Account-Id'], 7);
    },
  );
  test(
    'upload and removal retain exact operation identity and both revision scopes',
    () async {
      final adapter = _Files();
      // Use the same transport for both operations, as an actual client does.
      final client = ActivitiesApi(Dio()..httpClientAdapter = adapter);
      final upload = await client.uploadActivityMaterialFile(
        group: 3,
        material: 9,
        operationId: attachmentId,
        materialRevision: 1,
        document: MultipartFile.fromBytes([
          37,
          80,
          68,
          70,
        ], filename: 'synthetic.pdf'),
        xSchoolId: 1,
        xAccountId: 7,
      );
      expect(upload.data!.data.materialRevision, 2);
      final form = adapter.requests.first.data as FormData;
      expect(Map.fromEntries(form.fields), {
        'operation_id': attachmentId,
        'material_revision': '1',
      });
      expect(form.files.single.key, 'document');
      final removal = await client.removeActivityMaterialFile(
        group: 3,
        material: 9,
        file: attachmentId,
        activityMaterialAttachmentRemove: ActivityMaterialAttachmentRemove(
          operationId: attachmentId,
          revision: 1,
          materialRevision: 2,
        ),
        xSchoolId: 1,
        xAccountId: 7,
      );
      expect(removal.data!.data.removed, isTrue);
      expect(removal.data!.data.materialRevision, 3);
      expect(jsonDecode(adapter.requests.last.data as String), {
        'operation_id': attachmentId,
        'revision': 1,
        'material_revision': 2,
      });
      expect(
        adapter.requests.every(
          (r) =>
              r.headers['X-School-Id'] == 1 && r.headers['X-Account-Id'] == 7,
        ),
        isTrue,
      );
    },
  );
}
