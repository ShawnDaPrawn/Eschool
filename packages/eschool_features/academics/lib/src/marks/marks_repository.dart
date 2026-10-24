import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

export 'package:eschool_api/eschool_api.dart';

abstract interface class MarksRepository {
  Future<List<TeachingGroup>> groups();
  Future<List<SchoolTerm>> terms(TeachingGroup group);
  Future<AcademicMatrixData> matrix(int group, int term, {int page = 1});
  Future<AcademicPreviewData> preview(
    int group,
    int task,
    AcademicMarkWrite input,
  );
  Future<AcademicCaptureData> capture(
    int group,
    int task,
    AcademicMarkWrite input, {
    bool correction = false,
  });
  Future<AcademicCaptureData> moderate(
    int group,
    int task,
    AcademicModerationWrite input,
  );
  Future<AcademicReleasePreviewData> releasePreview(
    int group,
    AcademicReleaseWrite input,
  );
  Future<AcademicReleaseData> release(
    int group,
    AcademicReleaseCommitWrite input,
  );
  Future<AcademicReleasePeriodsData> periods(int learner);
  Future<AcademicReleasedData> released(int learner, int year, int? term);
}

class ApiMarksRepository implements MarksRepository {
  ApiMarksRepository(this.client, {required this.schoolId});
  final ApiClient client;
  final int schoolId;
  String get base => '${AppConfig.apiV2Url}academics/';

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? data,
    Map<String, dynamic>? query,
  }) async {
    final envelope = await client.send(
      method: method,
      url: '$base$path',
      schoolId: schoolId,
      data: data,
      queryParameters: query,
    );
    final result = envelope?['data'];
    if (result is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result);
  }

  @override
  Future<List<TeachingGroup>> groups() async {
    final groups = <TeachingGroup>[];
    var page = 1;
    while (true) {
      final result = await _send(
        'teaching-groups',
        query: {'page': page, 'per_page': 100},
      );
      groups.addAll(
        (result['groups'] as List).map(
          (row) =>
              TeachingGroup.fromJson(Map<String, dynamic>.from(row as Map)),
        ),
      );
      if (page >= (result['last_page'] as int)) break;
      if (++page > 100) {
        throw ApiException(
          'Choose a narrower school context.',
          isApiMessage: true,
        );
      }
    }
    return groups;
  }

  @override
  Future<List<SchoolTerm>> terms(TeachingGroup group) async {
    final reference = await _send('reference');
    final grades = reference['grades'] as List;
    final grade = grades.cast<Map>().firstWhere(
      (row) => row['id'] == group.gradeLevelId,
    );
    final result = await _send(
      'terms',
      query: {
        'session_year_id': group.sessionYearId,
        'grade': grade['sort_order'],
      },
    );
    return (result['terms'] as List)
        .map(
          (row) => SchoolTerm.fromJson(Map<String, dynamic>.from(row as Map)),
        )
        .toList();
  }

  @override
  Future<AcademicMatrixData> matrix(
    int group,
    int term, {
    int page = 1,
  }) async => AcademicMatrixData.fromJson(
    await _send(
      'marks/groups/$group',
      query: {'term_id': term, 'page': page, 'per_page': 100},
    ),
  );
  @override
  Future<AcademicPreviewData> preview(
    int group,
    int task,
    AcademicMarkWrite input,
  ) async => AcademicPreviewData.fromJson(
    await _send(
      'marks/groups/$group/tasks/$task/preview',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<AcademicCaptureData> capture(
    int group,
    int task,
    AcademicMarkWrite input, {
    bool correction = false,
  }) async => AcademicCaptureData.fromJson(
    await _send(
      'marks/groups/$group/tasks/$task/${correction ? 'correct' : 'capture'}',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<AcademicCaptureData> moderate(
    int group,
    int task,
    AcademicModerationWrite input,
  ) async => AcademicCaptureData.fromJson(
    await _send(
      'marks/groups/$group/tasks/$task/moderate',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<AcademicReleasePreviewData> releasePreview(
    int group,
    AcademicReleaseWrite input,
  ) async => AcademicReleasePreviewData.fromJson(
    await _send(
      'marks/groups/$group/release-preview',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<AcademicReleaseData> release(
    int group,
    AcademicReleaseCommitWrite input,
  ) async => AcademicReleaseData.fromJson(
    await _send(
      'marks/groups/$group/release',
      method: 'POST',
      data: input.toJson(),
    ),
  );
  @override
  Future<AcademicReleasePeriodsData> periods(int learner) async =>
      AcademicReleasePeriodsData.fromJson(
        await _send('marks/learners/$learner/periods'),
      );

  @override
  Future<AcademicReleasedData> released(
    int learner,
    int year,
    int? term,
  ) async => AcademicReleasedData.fromJson(
    await _send(
      'marks/learners/$learner',
      query: {'session_year_id': year, 'term_id': ?term},
    ),
  );
}
