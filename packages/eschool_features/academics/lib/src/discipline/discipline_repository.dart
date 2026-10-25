import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

enum DisciplineAction {
  create,
  review,
  hearing,
  plan,
  enroll,
  attendance,
  cancel,
  acknowledge,
}

/// Immutable, memory-only data for an explicit exact retry after an unknown outcome.
class DisciplineIntent {
  DisciplineIntent(
    this.action,
    Map<String, dynamic> input, {
    this.learner,
    this.record,
    this.detention,
    this.entry,
  }) : _encoded = jsonEncode(input);
  final DisciplineAction action;
  final int? learner, record, detention, entry;
  final String _encoded;
  Map<String, dynamic> get body => jsonDecode(_encoded) as Map<String, dynamic>;
}

abstract interface class DisciplineRepository {
  bool get isActive;
  int get readerId;
  Future<DisciplineConfiguration> configuration();
  Future<DisciplineRecordQueue> records(int year, {int page = 1});
  Future<DisciplineLearnerChoices> learners(
    int year, {
    int page = 1,
    String? search,
  });
  Future<DisciplineRecordDetail> record(int id);
  Future<DisciplineRecordHistory> history(int id);
  Future<DisciplineDetentions> detentions(int year, {int page = 1});
  Future<DisciplineDetentionDetail> detention(int id);
  Future<DisciplineSupervisors> supervisors(
    int year,
    int grade,
    String date, {
    int page = 1,
  });
  Future<DisciplineFamilyHistory> family(
    int learner,
    int? year, {
    int page = 1,
  });
  Future<void> write(DisciplineIntent intent);
}

class ApiDisciplineRepository implements DisciplineRepository {
  ApiDisciplineRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
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
        'Reopen school discipline to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _scope(bool allowed) {
    if (!allowed) {
      throw ApiException(
        'The discipline selection changed. Reopen it.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? body,
  }) async {
    _guard();
    final response = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}discipline/$path',
      queryParameters: query,
      data: body,
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
  Future<DisciplineConfiguration> configuration() async =>
      DisciplineConfiguration.fromJson(await _send('configuration'));
  @override
  Future<DisciplineRecordQueue> records(int year, {int page = 1}) async {
    final row = DisciplineRecordQueue.fromJson(
      await _send('records', query: {'session_year_id': year, 'page': page}),
    );
    _scope(
      row.sessionYearId == year &&
          row.page == page &&
          row.records.every((r) => r.sessionYearId == year),
    );
    return row;
  }

  @override
  Future<DisciplineLearnerChoices> learners(
    int year, {
    int page = 1,
    String? search,
  }) async {
    final row = DisciplineLearnerChoices.fromJson(
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
  Future<DisciplineRecordDetail> record(int id) async {
    final row = DisciplineRecordDetail.fromJson(await _send('records/$id'));
    _scope(row.id == id);
    return row;
  }

  @override
  Future<DisciplineRecordHistory> history(int id) async {
    final row = DisciplineRecordHistory.fromJson(
      await _send('records/$id/history'),
    );
    _scope(row.recordId == id);
    return row;
  }

  @override
  Future<DisciplineDetentions> detentions(int year, {int page = 1}) async {
    final row = DisciplineDetentions.fromJson(
      await _send('detentions', query: {'session_year_id': year, 'page': page}),
    );
    _scope(
      row.sessionYearId == year &&
          row.page == page &&
          row.sessions.every((s) => s.sessionYearId == year),
    );
    return row;
  }

  @override
  Future<DisciplineDetentionDetail> detention(int id) async {
    final row = DisciplineDetentionDetail.fromJson(
      await _send('detentions/$id'),
    );
    _scope(row.id == id);
    return row;
  }

  @override
  Future<DisciplineSupervisors> supervisors(
    int year,
    int grade,
    String date, {
    int page = 1,
  }) async {
    final row = DisciplineSupervisors.fromJson(
      await _send(
        'supervisors',
        query: {
          'session_year_id': year,
          'grade_level_id': grade,
          'on_date': date,
          'page': page,
        },
      ),
    );
    _scope(row.page == page);
    return row;
  }

  @override
  Future<DisciplineFamilyHistory> family(
    int learner,
    int? year, {
    int page = 1,
  }) async {
    final row = DisciplineFamilyHistory.fromJson(
      await _send(
        'family/learners/$learner',
        query: {'session_year_id': ?year, 'page': page},
      ),
    );
    _scope(
      row.learnerId == learner &&
          (year == null || row.sessionYearId == year) &&
          row.page == page,
    );
    return row;
  }

  @override
  Future<void> write(DisciplineIntent intent) async {
    final body = intent.body;
    final path = switch (intent.action) {
      DisciplineAction.create => 'learners/${intent.learner}/records',
      DisciplineAction.review => 'records/${intent.record}/review',
      DisciplineAction.hearing => 'records/${intent.record}/hearing',
      DisciplineAction.plan => 'detentions',
      DisciplineAction.enroll => 'detentions/${intent.detention}/entries',
      DisciplineAction.attendance =>
        'detentions/${intent.detention}/entries/${intent.entry}/attendance',
      DisciplineAction.cancel => 'detentions/${intent.detention}/cancel',
      DisciplineAction.acknowledge =>
        'family/records/${intent.record}/acknowledge',
    };
    final row = await _send(path, body: body);
    switch (intent.action) {
      case DisciplineAction.create:
        _scope(
          row['learner_id'] == intent.learner &&
              row['session_year_id'] == body['session_year_id'],
        );
      case DisciplineAction.review || DisciplineAction.hearing:
        _scope(row['id'] == intent.record);
      case DisciplineAction.enroll || DisciplineAction.attendance:
        _scope(
          row['session_id'] == intent.detention &&
              (intent.entry == null || row['entry_id'] == intent.entry),
        );
      case DisciplineAction.plan:
        _scope(
          row['session_year_id'] == body['session_year_id'] &&
              row['grade_level_id'] == body['grade_level_id'],
        );
      case DisciplineAction.cancel:
        _scope(row['id'] == intent.detention);
      case DisciplineAction.acknowledge:
        _scope(
          row['record_id'] == intent.record &&
              row['revision'] == body['expected_revision'],
        );
    }
  }
}
