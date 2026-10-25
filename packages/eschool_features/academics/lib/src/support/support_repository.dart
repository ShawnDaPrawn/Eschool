import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class SupportRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<SupportAccess> access();
  Future<SupportConfiguration> configuration(
    String audience, {
    String search = '',
    int page = 1,
    int? learnerId,
    int? staffId,
  });
  Future<SupportCases> cases({int page = 1});
  Future<SupportAssignments> assignments({int page = 1});
  Future<SupportTeacherList> instructions({int page = 1});
  Future<SupportPlan> plan(int id);
  Future<SupportReviews> reviews(int id, {int page = 1});
  Future<SupportVersions> versions(int id, {int page = 1});
  Future<SupportDisclosures> disclosures(int id, {int page = 1});
  Future<SupportWriteReceipt> write(
    String action,
    int? id,
    Map<String, dynamic> payload,
  );
}

class ApiSupportRepository implements SupportRepository {
  ApiSupportRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId;
  @override
  final int accountId;
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
        'Your access changed. Reopen Support plans after refreshing your account.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? query,
    Map<String, dynamic>? payload,
  }) async {
    _guard();
    final response = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}pastoral/support/$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: payload,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    final data = response?['data'];
    if (data is! Map) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<SupportAccess> access() async =>
      SupportAccess.fromJson(await _send('access'));
  @override
  Future<SupportConfiguration> configuration(
    String audience, {
    String search = '',
    int page = 1,
    int? learnerId,
    int? staffId,
  }) async {
    if (!['professional', 'principal'].contains(audience)) {
      throw ArgumentError.value(audience);
    }
    return SupportConfiguration.fromJson(
      await _send(
        'configuration/$audience',
        query: {
          if (search.trim().isNotEmpty) 'search': search.trim(),
          'page': page,
          'learner_id': ?learnerId,
          'user_id': ?staffId,
        },
      ),
    );
  }

  @override
  Future<SupportCases> cases({int page = 1}) async =>
      SupportCases.fromJson(await _send('cases', query: {'page': page}));
  @override
  Future<SupportAssignments> assignments({int page = 1}) async =>
      SupportAssignments.fromJson(
        await _send('assignments', query: {'page': page}),
      );
  @override
  Future<SupportTeacherList> instructions({int page = 1}) async =>
      SupportTeacherList.fromJson(
        await _send('teacher', query: {'page': page}),
      );
  @override
  Future<SupportPlan> plan(int id) async {
    final row = SupportPlan.fromJson(await _send('plans/$id'));
    if (row.id != id) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return row;
  }

  @override
  Future<SupportReviews> reviews(int id, {int page = 1}) async {
    final rows = SupportReviews.fromJson(
      await _send('plans/$id/reviews', query: {'page': page}),
    );
    if (rows.planId != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return rows;
  }

  @override
  Future<SupportVersions> versions(int id, {int page = 1}) async {
    final rows = SupportVersions.fromJson(
      await _send('plans/$id/versions', query: {'page': page}),
    );
    if (rows.planId != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return rows;
  }

  @override
  Future<SupportDisclosures> disclosures(int id, {int page = 1}) async {
    final rows = SupportDisclosures.fromJson(
      await _send('plans/$id/disclosures', query: {'page': page}),
    );
    if (rows.planId != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return rows;
  }

  @override
  Future<SupportWriteReceipt> write(
    String action,
    int? id,
    Map<String, dynamic> payload,
  ) async {
    final (path, method) = switch (action) {
      'assign' => ('assignments', 'POST'),
      'create' => ('plans', 'POST'),
      'save' => ('plans/$id', 'PUT'),
      'review' => ('plans/$id/reviews', 'POST'),
      'close' => ('plans/$id/close', 'POST'),
      'disclose' => ('plans/$id/disclosures', 'POST'),
      'revoke-assignment' => ('assignments/$id/revoke', 'POST'),
      'revoke-disclosure' => ('disclosures/$id/revoke', 'POST'),
      _ => throw ArgumentError.value(action),
    };
    if (!['assign', 'create'].contains(action) && (id == null || id <= 0)) {
      throw ArgumentError.value(id);
    }
    final receipt = SupportWriteReceipt.fromJson(
      await _send(path, method: method, payload: payload),
    );
    final kind = switch (action) {
      'assign' || 'revoke-assignment' => 'assignment',
      'review' => 'review',
      'disclose' || 'revoke-disclosure' => 'disclosure',
      _ => 'plan',
    };
    if (receipt.id <= 0 ||
        receipt.revision <= 0 ||
        receipt.kind.value != kind) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return receipt;
  }
}
