import 'dart:convert';
import 'dart:typed_data';
import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

class ContentUpload {
  ContentUpload(String name, Uint8List bytes)
    : name = sanitizeFileName(name),
      bytes = Uint8List.fromList(bytes).asUnmodifiableView();
  final String name;
  final Uint8List bytes;
}

abstract interface class AcademicContentRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<ContentConfiguration> configuration({int? year});
  Future<ContentList> items(int year, {int page = 1});
  Future<ContentItem> item(int id, {int page = 1});
  Future<ContentVersion> version(int id);
  Future<ContentReceipt> create(
    ContentSelection selection,
    ContentUpload source,
    String operation,
  );
  Future<ContentReceipt> replace(
    int item,
    ContentSelection selection,
    ContentUpload source,
    ContentRevisionWrite input,
  );
  Future<ContentReceipt> save(int version, ContentDraftWrite input);
  Future<ContentPreview> preview(int version);
  Future<ContentReceipt> publish(int version, ContentPublishWrite input);
  Future<ContentReceipt> retry(int version, ContentRevisionWrite input);
  Future<ContentReceipt> archive(int item, ContentArchiveWrite input);
  Future<ContentSettings> settings(ContentSettings input);
  Future<ContentLibrary> library(int learner, {int page = 1});
  Future<ContentReader> reader(int learner, int version);
  Future<void> read(int learner, int version);
}

class ApiAcademicContentRepository implements AcademicContentRepository {
  ApiAcademicContentRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId;
  @override
  final int readerId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your account or school changed. Reopen Learning content.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? data,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}academics/content/$path',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
    );
    _guard();
    final body = result?['data'];
    if (body is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(body);
  }

  @override
  Future<ContentConfiguration> configuration({int? year}) async =>
      ContentConfiguration.fromJson(
        await _send('configuration', query: {'session_year_id': ?year}),
      );
  @override
  Future<ContentList> items(int year, {int page = 1}) async =>
      ContentList.fromJson(
        await _send('items', query: {'session_year_id': year, 'page': page}),
      );
  @override
  Future<ContentItem> item(int id, {int page = 1}) async =>
      ContentItem.fromJson(await _send('items/$id', query: {'page': page}));
  @override
  Future<ContentVersion> version(int id) async =>
      ContentVersion.fromJson(await _send('versions/$id'));
  FormData _source(
    ContentSelection selection,
    ContentUpload file,
    String operation, {
    ContentRevisionWrite? revisions,
  }) {
    if (file.bytes.isEmpty || file.bytes.length > 25 * 1024 * 1024) {
      throw ApiException(
        'Choose a non-empty source file up to 25 MiB.',
        isApiMessage: true,
        statusCode: 422,
      );
    }
    return FormData.fromMap({
      'selection': jsonEncode(selection.toJson()),
      'source': MultipartFile.fromBytes(file.bytes, filename: file.name),
      'operation_id': operation,
      if (revisions != null) ...{
        'item_revision': revisions.itemRevision,
        'version_revision': revisions.versionRevision,
      },
    });
  }

  @override
  Future<ContentReceipt> create(
    ContentSelection selection,
    ContentUpload source,
    String operation,
  ) async => ContentReceipt.fromJson(
    await _send(
      'items',
      method: 'POST',
      data: _source(selection, source, operation),
    ),
  );
  @override
  Future<ContentReceipt> replace(
    int item,
    ContentSelection selection,
    ContentUpload source,
    ContentRevisionWrite input,
  ) async => ContentReceipt.fromJson(
    await _send(
      'items/$item/source',
      method: 'POST',
      data: _source(selection, source, input.operationId, revisions: input),
    ),
  );
  @override
  Future<ContentReceipt> save(int version, ContentDraftWrite input) async {
    if (utf8.encode(input.markdown).length > 2 * 1024 * 1024) {
      throw ApiException(
        'The Markdown draft must fit 2 MiB. Shorten it before saving.',
        isApiMessage: true,
        statusCode: 422,
      );
    }
    return ContentReceipt.fromJson(
      await _send('versions/$version', method: 'PUT', data: input.toJson()),
    );
  }

  @override
  Future<ContentPreview> preview(int version) async =>
      ContentPreview.fromJson(await _send('versions/$version/preview'));
  @override
  Future<ContentReceipt> publish(
    int version,
    ContentPublishWrite input,
  ) async => ContentReceipt.fromJson(
    await _send(
      'versions/$version/publish',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<ContentReceipt> retry(int version, ContentRevisionWrite input) async =>
      ContentReceipt.fromJson(
        await _send(
          'versions/$version/retry',
          method: 'POST',
          data: input.toJson(),
        ),
      );
  @override
  Future<ContentReceipt> archive(int item, ContentArchiveWrite input) async =>
      ContentReceipt.fromJson(
        await _send(
          'items/$item/archive',
          method: 'POST',
          data: input.toJson(),
        ),
      );
  @override
  Future<ContentSettings> settings(ContentSettings input) async =>
      ContentSettings.fromJson(
        await _send('settings', method: 'PUT', data: input.toJson()),
      );
  @override
  Future<ContentLibrary> library(int learner, {int page = 1}) async {
    final data = ContentLibrary.fromJson(
      await _send('learners/$learner', query: {'page': page}),
    );
    if (data.learnerId != learner || data.page != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return data;
  }

  @override
  Future<ContentReader> reader(int learner, int version) async {
    final data = ContentReader.fromJson(
      await _send('learners/$learner/versions/$version'),
    );
    if (data.id != version ||
        data.learnerId != learner ||
        data.schoolId != schoolId ||
        data.readerId != readerId ||
        sha256.convert(utf8.encode(data.markdown)).toString() !=
            data.bodySha256 ||
        data.offlineUntil.difference(data.serverTime) <= Duration.zero ||
        data.offlineUntil.difference(data.serverTime) >
            const Duration(days: 1)) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return data;
  }

  @override
  Future<void> read(int learner, int version) async {
    await _send('learners/$learner/versions/$version/read', method: 'POST');
  }
}
