import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class CoverRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<CoverConfiguration> configuration(
    String from,
    String to, {
    int? year,
    int page = 1,
  });
  Future<CoverAbsence> absence(int id);
  Future<CoverCandidates> candidates(int id);
  Future<List<CoverOccurrence>> mine(int year, String from, String to);
  Future<void> write(String action, int? id, Map<String, dynamic> payload);
}

class ApiCoverRepository implements CoverRepository {
  ApiCoverRepository(
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
        'Your account or school changed. Reopen Relief cover.',
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
    final result = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}pastoral/cover/$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: payload,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    final data = result?['data'];
    if (data is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<CoverConfiguration> configuration(
    String from,
    String to, {
    int? year,
    int page = 1,
  }) async => CoverConfiguration.fromJson(
    await _send(
      'configuration',
      query: {'from': from, 'to': to, 'session_year_id': ?year, 'page': page},
    ),
  );
  @override
  Future<CoverAbsence> absence(int id) async {
    final row = CoverAbsence.fromJson(await _send('absences/$id'));
    if (row.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return row;
  }

  @override
  Future<CoverCandidates> candidates(int id) async {
    final row = CoverCandidates.fromJson(
      await _send('occurrences/$id/candidates'),
    );
    if (row.occurrenceId != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return row;
  }

  @override
  Future<List<CoverOccurrence>> mine(int year, String from, String to) async =>
      CoverMine.fromJson(
        await _send(
          'mine',
          query: {'session_year_id': year, 'from': from, 'to': to},
        ),
      ).occurrences;
  @override
  Future<void> write(
    String action,
    int? id,
    Map<String, dynamic> payload,
  ) async {
    final (path, method) = switch (action) {
      'request' => ('absences', 'POST'),
      'assign' => ('occurrences/$id/assignment', 'PUT'),
      'refresh' || 'cancel' => ('absences/$id/$action', 'POST'),
      _ => throw ArgumentError.value(action),
    };
    await _send(path, method: method, payload: payload);
  }
}
