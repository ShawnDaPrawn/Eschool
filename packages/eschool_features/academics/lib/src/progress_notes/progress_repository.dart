import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ProgressRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<ProgressConfiguration> configuration();
  Future<ProgressRoster> roster(int group, {int page = 1});
  Future<ProgressLearnerList> caseload({int page = 1});
  Future<ProgressLearnerList> assignmentLearners({int page = 1});
  Future<LearnerNoteList> notes(
    int learner,
    int year,
    String visibility, {
    int? subject,
    int after = 0,
  });
  Future<LearnerNoteDetail> note(int id, int learner, String visibility);
  Future<LearnerNoteHistory> history(int id);
  Future<LearnerNoteDetail> create(int learner, LearnerNoteCreate input);
  Future<LearnerNoteDetail> update(int id, LearnerNoteUpdate input);
  Future<LearnerNoteAssignments> assignments(int learner);
  Future<LearnerNoteAssignment> assign(
    int learner,
    int staff,
    LearnerNoteAssignmentWrite input,
  );
  Future<LearnerNoteAssignment> revoke(
    int learner,
    int staff,
    LearnerNoteAssignmentRevoke input,
  );
  Future<ProgressStaffBand> staffBand(int group, int learner, int term);
  Future<AcademicReleasePeriodsData> periods(int learner);
  Future<ProgressFamilyBands> familyBands(int learner, int year, int? term);
}

/// No persisted note/draft cache. Context and bearer identity are pinned per route.
class ApiProgressRepository implements ProgressRepository {
  ApiProgressRepository(
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
        'Your account or school changed. Reopen progress and notes.',
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
      url: '${AppConfig.apiV2Url}growth/progress/$path',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
      requestTimeout: const Duration(seconds: 20),
      privateRequest: true,
    );
    _guard();
    if (response?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  void _scope(bool allowed) {
    if (!allowed) {
      throw ApiException(
        'The note selection changed. Reload before reading.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  @override
  Future<ProgressConfiguration> configuration() async {
    final row = ProgressConfiguration.fromJson(await _send('configuration'));
    _scope(row.groups.every((g) => g.sessionYearId == row.sessionYearId));
    return row;
  }

  @override
  Future<ProgressRoster> roster(int group, {int page = 1}) async {
    final row = ProgressRoster.fromJson(
      await _send('groups/$group/learners', query: {'page': page}),
    );
    _scope(row.groupId == group && row.currentPage == page);
    return row;
  }

  @override
  Future<ProgressLearnerList> caseload({int page = 1}) async {
    final row = ProgressLearnerList.fromJson(
      await _send('caseload', query: {'page': page}),
    );
    _scope(row.currentPage == page);
    return row;
  }

  @override
  Future<ProgressLearnerList> assignmentLearners({int page = 1}) async {
    final row = ProgressLearnerList.fromJson(
      await _send('assignment-learners', query: {'page': page}),
    );
    _scope(row.currentPage == page);
    return row;
  }

  @override
  Future<LearnerNoteList> notes(
    int learner,
    int year,
    String visibility, {
    int? subject,
    int after = 0,
  }) async {
    final row = LearnerNoteList.fromJson(
      await _send(
        'learners/$learner/notes',
        query: {
          'session_year_id': year,
          'visibility': visibility,
          'subject_id': ?subject,
          'after': after,
        },
      ),
    );
    _scope(
      row.notes.every(
        (n) =>
            n.learnerId == learner &&
            n.sessionYearId == year &&
            n.visibility.value == visibility &&
            (visibility == 'counselling' && subject == null ||
                n.subjectId == subject),
      ),
    );
    return row;
  }

  @override
  Future<LearnerNoteDetail> note(int id, int learner, String visibility) async {
    final row = LearnerNoteDetail.fromJson(await _send('notes/$id'));
    _scope(
      row.id == id &&
          row.learnerId == learner &&
          row.visibility.value == visibility,
    );
    return row;
  }

  @override
  Future<LearnerNoteHistory> history(int id) async {
    final row = LearnerNoteHistory.fromJson(await _send('notes/$id/history'));
    _scope(row.noteId == id);
    return row;
  }

  @override
  Future<LearnerNoteDetail> create(int learner, LearnerNoteCreate input) async {
    final row = LearnerNoteDetail.fromJson(
      await _send(
        'learners/$learner/notes',
        method: 'POST',
        data: input.toJson(),
      ),
    );
    _scope(
      row.learnerId == learner &&
          row.authorId == readerId &&
          row.sessionYearId == input.sessionYearId &&
          row.subjectId == input.subjectId &&
          row.visibility.value == input.visibility.value,
    );
    return row;
  }

  @override
  Future<LearnerNoteDetail> update(int id, LearnerNoteUpdate input) async {
    final row = LearnerNoteDetail.fromJson(
      await _send('notes/$id', method: 'PUT', data: input.toJson()),
    );
    _scope(row.id == id && row.authorId == readerId);
    return row;
  }

  @override
  Future<LearnerNoteAssignments> assignments(int learner) async {
    final row = LearnerNoteAssignments.fromJson(
      await _send('learners/$learner/assignments'),
    );
    _scope(
      row.learnerId == learner &&
          row.assignments.every((a) => a.learnerId == learner),
    );
    return row;
  }

  @override
  Future<LearnerNoteAssignment> assign(
    int learner,
    int staff,
    LearnerNoteAssignmentWrite input,
  ) async {
    final row = LearnerNoteAssignment.fromJson(
      await _send(
        'learners/$learner/assignments/$staff',
        method: 'PUT',
        data: input.toJson(),
      ),
    );
    _scope(row.learnerId == learner && row.staffUserId == staff);
    return row;
  }

  @override
  Future<LearnerNoteAssignment> revoke(
    int learner,
    int staff,
    LearnerNoteAssignmentRevoke input,
  ) async {
    final row = LearnerNoteAssignment.fromJson(
      await _send(
        'learners/$learner/assignments/$staff/revoke',
        method: 'POST',
        data: input.toJson(),
      ),
    );
    _scope(
      row.learnerId == learner &&
          row.staffUserId == staff &&
          row.revoked == true,
    );
    return row;
  }

  @override
  Future<ProgressStaffBand> staffBand(int group, int learner, int term) async {
    final row = ProgressStaffBand.fromJson(
      await _send('groups/$group/learners/$learner', query: {'term_id': term}),
    );
    _scope(
      row.learnerId == learner &&
          row.termId == term &&
          row.audience.value == 'staff',
    );
    return row;
  }

  @override
  Future<AcademicReleasePeriodsData> periods(int learner) async {
    final row = AcademicReleasePeriodsData.fromJson(
      await _send('learners/$learner/periods'),
    );
    _scope(row.learnerId == learner);
    return row;
  }

  @override
  Future<ProgressFamilyBands> familyBands(
    int learner,
    int year,
    int? term,
  ) async {
    final row = ProgressFamilyBands.fromJson(
      await _send(
        'learners/$learner/bands',
        query: {'session_year_id': year, 'term_id': ?term},
      ),
    );
    _scope(
      row.learnerId == learner &&
          row.audience.value == 'family' &&
          row.results.every((r) => r.sessionYearId == year && r.termId == term),
    );
    return row;
  }
}
