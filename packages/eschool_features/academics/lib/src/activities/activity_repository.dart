import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ActivityRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<ActivityGroupList> groups({int page = 1});
  Future<ActivityGroupDetail> detail(int group);
  Future<ActivityGroupRoster> roster(int group);
  Future<ActivitySession> session(
    int group,
    int? id,
    ActivitySessionWrite input,
  );
  Future<ActivityMaterial> material(
    int group,
    int? id,
    ActivityMaterialWrite input,
  );
  Future<ActivityAttendanceReceipt> attendance(
    int session,
    ActivityAttendanceWrite input,
  );
}

/// A private, in-memory school/account session. No persisted group cache.
class ApiActivityRepository implements ActivityRepository {
  ApiActivityRepository(
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
    if (!isActive) {
      throw ApiException(
        'Your account or school changed. Reopen Activity groups.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? data,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}activities/$path',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
      accountId: accountId,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (result?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  void _group(ActivityGroup row, [int? expected]) {
    if (row.schoolId != schoolId || (expected != null && row.id != expected)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
  }

  @override
  Future<ActivityGroupList> groups({int page = 1}) async {
    final result = ActivityGroupList.fromJson(
      await _send('groups', query: {'page': page}),
    );
    if (result.page != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    for (final row in result.items) {
      _group(row);
    }
    return result;
  }

  @override
  Future<ActivityGroupDetail> detail(int group) async {
    final result = ActivityGroupDetail.fromJson(await _send('groups/$group'));
    _group(result.group, group);
    if (result.sessions.any((x) => x.activityGroupId != group) ||
        result.materials.any((x) => x.activityGroupId != group)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
    return result;
  }

  @override
  Future<ActivityGroupRoster> roster(int group) async {
    final result = ActivityGroupRoster.fromJson(
      await _send('groups/$group/roster'),
    );
    if (result.memberships.any((x) => x.activityGroupId != group) ||
        result.staff.any((x) => x.activityGroupId != group)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
    return result;
  }

  @override
  Future<ActivitySession> session(
    int group,
    int? id,
    ActivitySessionWrite input,
  ) async {
    final result = ActivitySession.fromJson(
      await _send(
        'groups/$group/sessions${id == null ? '' : '/$id'}',
        method: id == null ? 'POST' : 'PUT',
        data: input.toJson(),
      ),
    );
    if (result.activityGroupId != group || (id != null && result.id != id)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
    return result;
  }

  @override
  Future<ActivityMaterial> material(
    int group,
    int? id,
    ActivityMaterialWrite input,
  ) async {
    if (utf8.encode(input.markdown).length > 65536) {
      throw ApiException(
        'Choose material up to 64 KiB.',
        isApiMessage: true,
        statusCode: 422,
      );
    }
    final result = ActivityMaterial.fromJson(
      await _send(
        'groups/$group/materials${id == null ? '' : '/$id'}',
        method: id == null ? 'POST' : 'PUT',
        data: input.toJson(),
      ),
    );
    if (result.activityGroupId != group || (id != null && result.id != id)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
    return result;
  }

  @override
  Future<ActivityAttendanceReceipt> attendance(
    int session,
    ActivityAttendanceWrite input,
  ) async {
    final result = ActivityAttendanceReceipt.fromJson(
      await _send(
        'sessions/$session/attendance',
        method: 'PUT',
        data: input.toJson(),
      ),
    );
    if (result.items.any((x) => x.activitySessionId != session)) {
      throw ApiException('activity_scope_changed', statusCode: 403);
    }
    return result;
  }
}
