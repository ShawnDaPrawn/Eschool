import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ConsultationRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<ConsultationAccess> access();
  Future<ConsultationPage> listing({int page = 1});
  Future<ConsultationBallot> ballot(int id);
  Future<ConsultationVoteReceipt> vote(
    int id,
    List<ConsultationVoteAnswer> answers,
  );
  Future<ConsultationResults> results(int id);
}

/// Ballots and drafts remain inside one fresh account/school authorization epoch.
class ApiConsultationRepository implements ConsultationRepository {
  ApiConsultationRepository(
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
        'Refresh your account before opening consultations.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _id(int value) {
    if (value <= 0) throw ArgumentError.value(value);
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? body,
  }) async {
    _guard();
    final response = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}consultations$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: body,
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
  Future<ConsultationAccess> access() async {
    final result = ConsultationAccess.fromJson(await _send('/access'));
    if (result.schoolId != schoolId || result.accountId != accountId) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
    return result;
  }

  @override
  Future<ConsultationPage> listing({int page = 1}) async {
    _id(page);
    final result = ConsultationPage.fromJson(
      await _send('', query: {'page': page}),
    );
    if (result.page != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<ConsultationBallot> ballot(int id) async {
    _id(id);
    final result = ConsultationBallot.fromJson(await _send('/$id'));
    if (result.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<ConsultationVoteReceipt> vote(
    int id,
    List<ConsultationVoteAnswer> answers,
  ) async {
    _id(id);
    // No actor-linked idempotency key, answer hash or anonymous receipt reference.
    return ConsultationVoteReceipt.fromJson(
      await _send(
        '/$id/vote',
        body: ConsultationVoteInput(answers: answers).toJson(),
      ),
    );
  }

  @override
  Future<ConsultationResults> results(int id) async {
    _id(id);
    final result = ConsultationResults.fromJson(await _send('/$id/results'));
    if (result.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }
}
