import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class RiskRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<RiskConfiguration> configuration();
  Future<RiskSignals> signals(int year, {int page = 1});
  Future<RiskClassroom> classroom(int group);
  Future<InterventionQueue> queue(int year, {int page = 1});
  Future<RiskLearnerChoices> learners(int year, {int page = 1, String? search});
  Future<RiskOwnerChoices> owners(
    int learner,
    int year, {
    int? intervention,
    int page = 1,
    String? search,
  });
  Future<RiskProfessionalChoices> professionals(
    int learner,
    int year, {
    int page = 1,
    String? search,
  });
  Future<RiskAssignments> assignments(int learner, int year);
  Future<InterventionDetail> detail(int id);
  Future<InterventionHistory> history(int id);
  Future<InterventionMetadata> create(int learner, InterventionCreate input);
  Future<InterventionMetadata> update(int id, InterventionUpdate input);
  Future<InterventionMetadata> transfer(int id, InterventionOwnerWrite input);
  Future<RiskAssignmentResult> assign(
    int learner,
    int professional,
    RiskAssignmentWrite input,
  );
  Future<RiskAssignmentResult> revoke(
    int learner,
    int professional,
    RiskAssignmentRevoke input,
  );
}

/// Private text/receipts are never persisted. Every route pins school/account/token.
class ApiRiskRepository implements RiskRepository {
  ApiRiskRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, readerId;
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
        'Your account or school changed. Reopen staff follow-up.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  void _scope(bool ok) {
    if (!ok) {
      throw ApiException(
        'The follow-up selection changed. Reload before reading.',
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
    final response = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}growth/risk/$path',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
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
  Future<RiskConfiguration> configuration() async {
    final row = RiskConfiguration.fromJson(await _send('configuration'));
    _scope(row.groups.every((g) => g.sessionYearId == row.sessionYearId));
    return row;
  }

  @override
  Future<RiskSignals> signals(int year, {int page = 1}) async {
    final row = RiskSignals.fromJson(
      await _send('signals', query: {'session_year_id': year, 'page': page}),
    );
    _scope(row.sessionYearId == year && row.page == page);
    return row;
  }

  @override
  Future<RiskClassroom> classroom(int group) async {
    final row = RiskClassroom.fromJson(await _send('groups/$group'));
    _scope(row.groupId == group);
    return row;
  }

  @override
  Future<InterventionQueue> queue(int year, {int page = 1}) async {
    final row = InterventionQueue.fromJson(
      await _send(
        'interventions',
        query: {'session_year_id': year, 'page': page},
      ),
    );
    _scope(
      row.sessionYearId == year &&
          row.page == page &&
          row.interventions.every(
            (r) => !r.canReadBody || r.ownerId == readerId,
          ),
    );
    return row;
  }

  @override
  Future<RiskLearnerChoices> learners(
    int year, {
    int page = 1,
    String? search,
  }) async {
    final row = RiskLearnerChoices.fromJson(
      await _send(
        'learners',
        query: {
          'session_year_id': year,
          'page': page,
          if (search != null && search.trim().isNotEmpty)
            'search': search.trim(),
        },
      ),
    );
    _scope(row.sessionYearId == year && row.page == page);
    return row;
  }

  @override
  Future<RiskOwnerChoices> owners(
    int learner,
    int year, {
    int? intervention,
    int page = 1,
    String? search,
  }) async {
    final row = RiskOwnerChoices.fromJson(
      await _send(
        'learners/$learner/owners',
        query: {
          'session_year_id': year,
          'page': page,
          'intervention_id': ?intervention,
          if (search != null && search.trim().isNotEmpty)
            'search': search.trim(),
        },
      ),
    );
    _scope(row.page == page);
    return row;
  }

  @override
  Future<RiskProfessionalChoices> professionals(
    int learner,
    int year, {
    int page = 1,
    String? search,
  }) async {
    final row = RiskProfessionalChoices.fromJson(
      await _send(
        'learners/$learner/professionals',
        query: {
          'session_year_id': year,
          'page': page,
          if (search != null && search.trim().isNotEmpty)
            'search': search.trim(),
        },
      ),
    );
    _scope(row.page == page);
    return row;
  }

  @override
  Future<RiskAssignments> assignments(int learner, int year) async {
    final row = RiskAssignments.fromJson(
      await _send(
        'learners/$learner/assignments',
        query: {'session_year_id': year},
      ),
    );
    _scope(row.learnerId == learner && row.sessionYearId == year);
    return row;
  }

  @override
  Future<InterventionDetail> detail(int id) async {
    final row = InterventionDetail.fromJson(await _send('interventions/$id'));
    _scope(row.id == id && row.ownerId == readerId);
    return row;
  }

  @override
  Future<InterventionHistory> history(int id) async {
    final row = InterventionHistory.fromJson(
      await _send('interventions/$id/history'),
    );
    _scope(row.interventionId == id);
    return row;
  }

  @override
  Future<InterventionMetadata> create(
    int learner,
    InterventionCreate input,
  ) async {
    final row = InterventionMetadata.fromJson(
      await _send(
        'learners/$learner/interventions',
        method: 'POST',
        data: input.toJson(),
      ),
    );
    _scope(
      row.learnerId == learner &&
          row.sessionYearId == input.sessionYearId &&
          row.ownerId == input.ownerId,
    );
    return row;
  }

  @override
  Future<InterventionMetadata> update(int id, InterventionUpdate input) async {
    final row = InterventionMetadata.fromJson(
      await _send('interventions/$id', method: 'PUT', data: input.toJson()),
    );
    _scope(row.id == id && row.ownerId == readerId);
    return row;
  }

  @override
  Future<InterventionMetadata> transfer(
    int id,
    InterventionOwnerWrite input,
  ) async {
    final row = InterventionMetadata.fromJson(
      await _send(
        'interventions/$id/owner',
        method: 'POST',
        data: input.toJson(),
      ),
    );
    _scope(row.id == id && row.ownerId == input.ownerId);
    return row;
  }

  @override
  Future<RiskAssignmentResult> assign(
    int learner,
    int professional,
    RiskAssignmentWrite input,
  ) async => RiskAssignmentResult.fromJson(
    await _send(
      'learners/$learner/assignments/$professional',
      method: 'PUT',
      data: input.toJson(),
    ),
  );
  @override
  Future<RiskAssignmentResult> revoke(
    int learner,
    int professional,
    RiskAssignmentRevoke input,
  ) async => RiskAssignmentResult.fromJson(
    await _send(
      'learners/$learner/assignments/$professional/revoke',
      method: 'POST',
      data: input.toJson(),
    ),
  );
}
