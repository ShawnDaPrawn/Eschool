import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class PrivateGalleryRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<GalleryConfiguration> configuration();
  Future<GalleryFamilyPage> family(
    int learner, {
    bool pending = false,
    int? before,
    int? year,
  });
  Future<GalleryGroupPage> group(int group, {int? before});
  Future<Uint8List> photo(int id, {bool thumbnail = true});
  Future<GalleryReference> upload(
    Map<String, dynamic> payload,
    Uint8List bytes,
  );
  Future<GalleryReference> change(
    String action,
    int id,
    Map<String, dynamic> payload,
  );
}

class ApiPrivateGalleryRepository implements PrivateGalleryRepository {
  ApiPrivateGalleryRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive)
      throw ApiException(
        'Your photo access changed. Reopen the gallery.',
        statusCode: 403,
        isApiMessage: true,
      );
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}gallery/$path',
      schoolId: schoolId,
      accountId: accountId,
      data: body,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (result?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  @override
  Future<GalleryConfiguration> configuration() async {
    final value = GalleryConfiguration.fromJson(await _send('configuration'));
    if (value.schoolId != schoolId || value.accountId != accountId)
      throw ApiException(
        'Your photo account changed.',
        statusCode: 403,
        isApiMessage: true,
      );
    return value;
  }

  @override
  Future<GalleryFamilyPage> family(
    int learner, {
    bool pending = false,
    int? before,
    int? year,
  }) async {
    if (learner <= 0) throw ArgumentError.value(learner);
    return GalleryFamilyPage.fromJson(
      await _send(
        'learners/$learner/photos',
        query: {
          'pending': pending,
          if (before != null) 'before': before,
          if (year != null) 'session_year_id': year,
        },
      ),
    );
  }

  @override
  Future<GalleryGroupPage> group(int group, {int? before}) async {
    if (group <= 0) throw ArgumentError.value(group);
    return GalleryGroupPage.fromJson(
      await _send(
        'groups/$group/photos',
        query: {if (before != null) 'before': before},
      ),
    );
  }

  @override
  Future<Uint8List> photo(int id, {bool thumbnail = true}) async {
    if (id <= 0) throw ArgumentError.value(id);
    _guard();
    final bytes = await client.getBytes(
      url:
          '${AppConfig.apiV2Url}gallery/photos/$id/content?thumbnail=${thumbnail ? 1 : 0}',
      schoolId: schoolId,
      accountId: accountId,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (bytes.length < 4 ||
        bytes.length > 4194304 ||
        bytes[0] != 0xff ||
        bytes[1] != 0xd8)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return bytes;
  }

  @override
  Future<GalleryReference> upload(
    Map<String, dynamic> payload,
    Uint8List bytes,
  ) async {
    _guard();
    if (bytes.isEmpty || bytes.length > 4194304)
      throw ApiException(
        'Choose a JPEG or PNG up to 4 MB.',
        isApiMessage: true,
      );
    final body = FormData.fromMap({
      ...payload,
      'photo': MultipartFile.fromBytes(bytes, filename: 'photo.jpg'),
    });
    final result = GalleryReference.fromJson(await _send('photos', body: body));
    if (result.id <= 0 || result.revision <= 0)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return result;
  }

  @override
  Future<GalleryReference> change(
    String action,
    int id,
    Map<String, dynamic> payload,
  ) async {
    if (id <= 0) throw ArgumentError.value(id);
    final path = switch (action) {
      'propose' => 'photos/$id/tags',
      'withdraw' => 'photos/$id/withdraw',
      'decide' => 'tags/$id/decision',
      'remove' => 'tags/$id/remove',
      _ => throw ArgumentError.value(action),
    };
    final result = GalleryReference.fromJson(await _send(path, body: payload));
    if (result.id <= 0 ||
        result.revision <= 0 ||
        action != 'propose' && result.id != id)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return result;
  }
}
