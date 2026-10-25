import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class PrivateStaffNoticeRepository {
  bool get isActive;
  Future<StaffPrivateNoticePage> notices(int page);
  Future<StaffPrivateNoticeRead> markRead(int id);
}

/// Dedicated staff context, never the legacy Teacher/Firebase notification route.
class ApiPrivateStaffNoticeRepository implements PrivateStaffNoticeRepository {
  ApiPrivateStaffNoticeRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
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
    if (!isActive) {
      throw ApiException(
        'Refresh staff access before opening private updates.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _scope(int school, int reader) {
    if (school != schoolId || reader != accountId) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    int? page,
    bool write = false,
  }) async {
    _guard();
    final response = await client.send(
      method: write ? 'POST' : 'GET',
      url: '${AppConfig.apiV2Url}staff/private-notices$path',
      data: write ? <String, dynamic>{} : null,
      queryParameters: page == null ? null : {'page': page},
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (response?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  @override
  Future<StaffPrivateNoticePage> notices(int page) async {
    final result = StaffPrivateNoticePage.fromJson(await _send('', page: page));
    _scope(result.schoolId, result.readerId);
    if (result.page != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<StaffPrivateNoticeRead> markRead(int id) async {
    final result = StaffPrivateNoticeRead.fromJson(
      await _send('/$id/read', write: true),
    );
    _scope(result.schoolId, result.readerId);
    if (result.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }
}
