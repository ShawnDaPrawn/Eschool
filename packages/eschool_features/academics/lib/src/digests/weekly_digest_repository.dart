import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class WeeklyDigestRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<WeeklyDigestAccess> access();
  Future<WeeklyDigestPage> weeks(int learner, {int? before});
  Future<WeeklyDigestDetail> detail(int id);
  Future<WeeklyDigestReference> preference(Map<String, dynamic> data);
}

class ApiWeeklyDigestRepository implements WeeklyDigestRepository {
  ApiWeeklyDigestRepository(
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
        'Your weekly update access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}digests/$path',
      data: body,
      queryParameters: query,
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (result?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  @override
  Future<WeeklyDigestAccess> access() async {
    final value = WeeklyDigestAccess.fromJson(await _send('access'));
    if (value.schoolId != schoolId ||
        value.accountId != accountId ||
        !value.parent)
      throw ApiException(
        'Your weekly update access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    return value;
  }

  @override
  Future<WeeklyDigestPage> weeks(int learner, {int? before}) async {
    if (learner <= 0) throw ArgumentError.value(learner);
    final page = WeeklyDigestPage.fromJson(
      await _send(
        'learners/$learner/weeks',
        query: {if (before != null) 'before': before},
      ),
    );
    if (page.items.any((row) => row.learnerId != learner))
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return page;
  }

  @override
  Future<WeeklyDigestDetail> detail(int id) async {
    if (id <= 0) throw ArgumentError.value(id);
    final value = WeeklyDigestDetail.fromJson(await _send('$id'));
    if (value.id != id || !value.expiresAt.isAfter(DateTime.now().toUtc()))
      throw ApiException(
        'This weekly update is no longer available.',
        statusCode: 404,
        isApiMessage: true,
      );
    return value;
  }

  @override
  Future<WeeklyDigestReference> preference(Map<String, dynamic> data) async =>
      WeeklyDigestReference.fromJson(await _send('preferences', body: data));
}
