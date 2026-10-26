import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ActivityFileProvider {
  ActivityFileRepository files(int group, int material);
}

abstract interface class ActivityFileRepository {
  int get schoolId;
  int get accountId;
  int get groupId;
  int get materialId;
  bool get isActive;
  Future<ActivityMaterialAttachmentPage> listing();
  Future<ActivityMaterialAttachmentChange> upload(MaterialFileIntent intent);
  Future<ActivityMaterialAttachmentChange> remove(
    String file,
    ActivityMaterialAttachmentRemove intent,
  );
  Future<Uint8List> read(ActivityMaterialAttachment attachment);
}

/// Foreground-only exact upload intent. The context owner must erase on exit.
class MaterialFileIntent {
  MaterialFileIntent({
    required this.operationId,
    required this.materialRevision,
    required String name,
    required this.bytes,
  }) : name = sanitizeFileName(name);
  final String operationId, name;
  final int materialRevision;
  final Uint8List bytes;
  bool _erased = false;
  bool get erased => _erased;
  void erase() {
    _erased = true;
    bytes.fillRange(0, bytes.length, 0);
  }
}

class ApiActivityFileRepository implements ActivityFileRepository {
  ApiActivityFileRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.groupId,
    required this.materialId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId, groupId, materialId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      groupId > 0 &&
      materialId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  String get _base =>
      '${AppConfig.apiV2Url}activities/groups/$groupId/materials/$materialId/files';
  Never _denied() => throw ApiException(
    'Your group access changed. Reopen Activity groups.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _denied();
  }

  bool _uuid(String value) => RegExp(
    r'^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
  ).hasMatch(value);
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? data,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '$_base$path',
      data: data,
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (result?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  void _attachment(ActivityMaterialAttachment file) {
    if (file.activityGroupId != groupId ||
        file.materialId != materialId ||
        !_uuid(file.id) ||
        file.removed ||
        file.bytes < 1 ||
        file.bytes > 5 * 1024 * 1024 ||
        !['application/pdf', 'image/jpeg'].contains(file.mime.value)) {
      _denied();
    }
  }

  @override
  Future<ActivityMaterialAttachmentPage> listing() async {
    final page = ActivityMaterialAttachmentPage.fromJson(await _send(''));
    if (page.materialRevision < 1 || page.items.length > 10) _denied();
    for (final file in page.items) {
      _attachment(file);
    }
    return page;
  }

  void _change(ActivityMaterialAttachmentChange change) {
    if (change.activityGroupId != groupId ||
        change.materialId != materialId ||
        !_uuid(change.id) ||
        change.materialRevision < 1) {
      _denied();
    }
  }

  @override
  Future<ActivityMaterialAttachmentChange> upload(
    MaterialFileIntent intent,
  ) async {
    if (!_uuid(intent.operationId) ||
        intent.materialRevision < 1 ||
        intent.bytes.isEmpty ||
        intent.bytes.length > 5 * 1024 * 1024) {
      throw ArgumentError('Choose a bounded document and exact operation.');
    }
    if (!(await listing()).canUpload) _denied();
    if (intent.erased) _denied();
    // The wire owns a bounded copy: clearing a UI draft cannot mutate a
    // multipart stream that has already begun sending under its original scope.
    final wire = Uint8List.fromList(intent.bytes);
    try {
      final result = ActivityMaterialAttachmentChange.fromJson(
        await _send(
          '',
          method: 'POST',
          data: FormData.fromMap({
            'operation_id': intent.operationId,
            'material_revision': intent.materialRevision,
            'document': MultipartFile.fromBytes(wire, filename: intent.name),
          }),
        ),
      );
      _change(result);
      if (result.removed ||
          result.materialRevision != intent.materialRevision + 1) {
        _denied();
      }
      return result;
    } finally {
      wire.fillRange(0, wire.length, 0);
    }
  }

  @override
  Future<ActivityMaterialAttachmentChange> remove(
    String file,
    ActivityMaterialAttachmentRemove intent,
  ) async {
    if (!_uuid(file) || !_uuid(intent.operationId)) {
      throw ArgumentError('Invalid document operation.');
    }
    if (!(await listing()).canRemove) _denied();
    final result = ActivityMaterialAttachmentChange.fromJson(
      await _send('/$file/remove', method: 'POST', data: intent.toJson()),
    );
    _change(result);
    if (result.id != file ||
        !result.removed ||
        result.revision != intent.revision + 1 ||
        result.materialRevision != intent.materialRevision + 1) {
      _denied();
    }
    return result;
  }

  @override
  Future<Uint8List> read(ActivityMaterialAttachment attachment) async {
    _guard();
    _attachment(attachment);
    final before = await listing();
    if (!before.items.any(
      (f) => f.id == attachment.id && f.revision == attachment.revision,
    )) {
      _denied();
    }
    var expired = false;
    final future = client
        .getBytes(
          url: '$_base/${attachment.id}/content',
          schoolId: schoolId,
          accountId: accountId,
          requestTimeout: const Duration(seconds: 25),
        )
        .then((bytes) {
          if (expired || !isActive) {
            bytes.fillRange(0, bytes.length, 0);
            _denied();
          }
          return bytes;
        });
    final bytes = await future.timeout(
      const Duration(seconds: 25),
      onTimeout: () {
        expired = true;
        throw ApiException(
          'The document timed out. Try again.',
          isApiMessage: true,
        );
      },
    );
    try {
      if (bytes.length != attachment.bytes || bytes.length > 5 * 1024 * 1024) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      final after = await listing();
      if (!after.items.any(
        (f) => f.id == attachment.id && f.revision == attachment.revision,
      )) {
        _denied();
      }
      _guard();
      return bytes;
    } catch (_) {
      bytes.fillRange(0, bytes.length, 0);
      rethrow;
    }
  }
}
