import 'package:eschool_core/eschool_core.dart';
import '../marks/marks_repository.dart';

abstract interface class TimetableRepository {
  int get schoolId;
  Future<AcademicTimetableWeekData> week({
    String? startsOn,
    int? year,
    int? learner,
  });
  Future<AcademicTimetableNextData> next({int? learner});
  Future<AcademicTimetableConfigurationData> configuration(int year);
  Future<List<TeachingGroup>> groups(int year);
  Future<AcademicTimetablePreviewData> preview(List<AcademicLessonWrite> rows);
  Future<AcademicTimetableCommitData> commit(
    AcademicTimetableCommitWrite input,
  );
}

class ApiTimetableRepository implements TimetableRepository {
  ApiTimetableRepository(this.client, {required this.schoolId});
  final ApiClient client;
  @override
  final int schoolId;
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? query,
    Object? data,
  }) async {
    final envelope = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}academics/timetable/$path',
      schoolId: schoolId,
      queryParameters: query,
      data: data,
    );
    final body = envelope?['data'];
    if (body is! Map) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(body);
  }

  @override
  Future<AcademicTimetableWeekData> week({
    String? startsOn,
    int? year,
    int? learner,
  }) async => AcademicTimetableWeekData.fromJson(
    await _send(
      'week',
      query: {
        'starts_on': ?startsOn,
        'session_year_id': ?year,
        'learner_id': ?learner,
      },
    ),
  );
  @override
  Future<AcademicTimetableNextData> next({int? learner}) async =>
      AcademicTimetableNextData.fromJson(
        await _send('next', query: {'learner_id': ?learner}),
      );
  @override
  Future<AcademicTimetableConfigurationData> configuration(int year) async =>
      AcademicTimetableConfigurationData.fromJson(
        await _send('configuration', query: {'session_year_id': year}),
      );
  @override
  Future<List<TeachingGroup>> groups(int year) async =>
      (await ApiMarksRepository(client, schoolId: schoolId).groups())
          .where((group) => group.sessionYearId == year && group.active)
          .toList();
  @override
  Future<AcademicTimetablePreviewData> preview(
    List<AcademicLessonWrite> rows,
  ) async => AcademicTimetablePreviewData.fromJson(
    await _send(
      'preview',
      method: 'POST',
      data: AcademicTimetablePreviewWrite(rows: rows).toJson(),
    ),
  );
  @override
  Future<AcademicTimetableCommitData> commit(
    AcademicTimetableCommitWrite input,
  ) async => AcademicTimetableCommitData.fromJson(
    await _send('commit', method: 'POST', data: input.toJson()),
  );
}
