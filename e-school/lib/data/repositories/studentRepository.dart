import 'package:eschool/data/models/attendanceDay.dart';
import 'package:eschool/data/models/customNotification.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/utils/api.dart';

class StudentRepository {
  //get all dashboard data for students
  Future<Map<String, dynamic>> fetchStudentDashboard() async {
    return (await Api.get(
      url: Api.studentDashboard,
      useAuthToken: true,
    ))['data'];
  }

  Future<void> selectElectiveSubjects({
    required Map<int, List<int>> electedSubjectGroups,
  }) async {
    final electedSubjectGroupIds = electedSubjectGroups.keys
        .map((key) => {'id': key, 'subject_id': electedSubjectGroups[key]})
        .toList();

    final body = {'subject_group': electedSubjectGroupIds};
    await Api.post(
      url: Api.selectStudentElectiveSubjects,
      useAuthToken: true,
      body: body,
    );
  }

  Future<Map<String, dynamic>> fetchParentDetails() async {
    try {
      final result = await Api.get(
        url: Api.parentDetailsOfStudent,
        useAuthToken: true,
      );

      return {
        'mother':
            result['data']['mother'] != null &&
                result['data']['mother'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['mother']))
            : null,
        'father':
            result['data']['father'] != null &&
                result['data']['father'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['father']))
            : null,
        'guardian':
            result['data']['guardian'] != null &&
                result['data']['guardian'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['guardian']))
            : null,
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<TimeTableSlot>> fetchTimeTable({
    required bool useParentApi,
    required int childId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentTimetableParent
            : Api.studentTimeTable,
        useAuthToken: true,
        queryParameters: useParentApi ? {'child_id': childId} : null,
      );

      return (result['data'] as List)
          .map((e) => TimeTableSlot.fromJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> fetchExamResults({
    required bool useParentApi,
    required int childId,
    int? page,
    String? termId,
    int? sessionYearId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'page': page ?? 0,
        if (termId != null) 'term_id': termId,
        if (sessionYearId != null) 'session_year_id': sessionYearId,
      };
      if (queryParameters['page'] == 0) {
        queryParameters.remove('page');
      }
      if (useParentApi) {
        queryParameters.addAll({'child_id': childId});
      }
      final result = await Api.get(
        url: useParentApi ? Api.getStudentResultsParent : Api.studentResults,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return {
        'results': ((result['data'] ?? []) as List)
            .map((result) => Result.fromJson(Map.from(result)))
            .toList(),
        'currentPage': result['data'] is List
            ? 1
            : (result['data']?['current_page'] ?? 1),
        'totalPage': result['data'] is List
            ? 1
            : (result['data']?['last_page'] ?? 1),
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> fetchOnlineExamResults({
    required bool useParentApi,
    required int childId,
    int? page,
    int? sessionYearId,
    int? subjectId,
  }) async {
    try {
      // final Map<String, dynamic> queryParameters = {
      //   if (page != null && page > 0) 'page': page,
      //   if (sessionYearId != null) 'session_year_id': sessionYearId,
      //   if (subjectId != null && subjectId != 0) 'subject_id': subjectId,
      //   if (useParentApi) 'child_id': childId,
      // };

      // Mock data for now - replace with actual API call
      await Future.delayed(const Duration(milliseconds: 800));
      return {
        'results': [
          {
            'online_exam_id': 1,
            'title': '1. First Chapter',
            'exam_submitted_date': '05, Jul, 2025',
            'subject': {'id': 1, 'name': 'Mathematics', 'type': 'theory'},
            'total_marks': '100',
            'obtained_marks': '63',
          },
          {
            'online_exam_id': 2,
            'title': '1. First Chapter',
            'exam_submitted_date': '05, Jul, 2025',
            'subject': {'id': 1, 'name': 'Mathematics', 'type': 'theory'},
            'total_marks': '100',
            'obtained_marks': '63',
          },
        ],
        'currentPage': 1,
        'totalPage': 1,
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<AttendanceReport> fetchAttendance({
    required int month,
    required int year,
    required bool useParentApi,
    required int childId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'month': month,
        'year': year,
      };

      if (useParentApi) {
        queryParameters.addAll({'child_id': childId});
      }

      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentAttendanceParent
            : Api.getStudentAttendance,
        queryParameters: queryParameters,
        useAuthToken: true,
      );

      return AttendanceReport.fromJson(
        Map<String, dynamic>.from(result['data'] ?? {}),
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  //
  //This method is used to fetch exams list
  Future<List<Exam>> fetchExamsList({
    required bool useParentApi,
    required int childId,
    required int examStatus,
    bool getTimetable = false,
    String? termId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi ? Api.getStudentExamListParent : Api.studentExamList,
        useAuthToken: true,
        queryParameters: useParentApi
            ? {
                'child_id': childId,
                'status': examStatus,
                if (getTimetable) 'get_timetable': '1',
                if (termId != null) 'term_id': termId,
              }
            : {
                'status': examStatus,
                if (getTimetable) 'get_timetable': '1',
                if (termId != null) 'term_id': termId,
              },
      );

      return (result['data'] as List)
          .map((e) => Exam.fromExamJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  //
  //This method is used to fetch time-table of particular exam
  Future<List<ExamTimeTable>> fetchExamTimeTable({
    required bool useParentApi,
    required int childId,
    required int examId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentExamDetailsParent
            : Api.studentExamDetails,
        useAuthToken: true,
        queryParameters: useParentApi
            ? {'child_id': childId, 'exam_id': examId}
            : {'exam_id': examId},
      );

      return (result['data'] as List)
          .map((e) => ExamTimeTable.fromJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> fetchNotifications({required int page}) async {
    try {
      final response = await Api.get(
        url: Api.getStudentNotifications,
        useAuthToken: true,
        queryParameters: {'page': page},
      );

      final List<CustomNotification> notifications = [];

      for (int i = 0; i < response['data']['data'].length; i++) {
        notifications.add(
          CustomNotification.fromJson(response['data']['data'][i]),
        );
      }

      return {
        'notifications': notifications,
        'currentPage': response['data']['current_page'],
        'totalPage': response['data']['last_page'],
      };
    } catch (error) {
      throw ApiException(error.toString());
    }
  }
}
