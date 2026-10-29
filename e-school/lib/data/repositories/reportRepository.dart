import 'package:eschool/utils/api.dart';
import 'package:eschool/ui/screens/reports/models/attendanceReport.dart';
import 'package:eschool/ui/screens/reports/models/assignmentReport.dart';
import 'package:eschool/ui/screens/reports/models/leaveReport.dart';
import 'package:eschool/ui/screens/reports/models/offlineExamReport.dart';
import 'package:eschool/ui/screens/reports/models/onlineExamReport.dart';

class ReportRepository {
  Future<LeaveReport> getLeaveReport({
    required int sessionYearId,
    int? childId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'session_year_id': sessionYearId,
      };
      if (childId != null && childId != 0) {
        queryParameters.addAll({'child_id': childId});
      }

      final response = await Api.get(
        url: childId != null ? Api.parentLeaveReport : Api.studentLeaveReport,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return LeaveReport.fromJson(Map.from(response['data'] ?? {}));
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<AssignmentReport> getAssignmentReport({
    required int sessionYearId,
    int? childId,
    int? subjectId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'session_year_id': sessionYearId,
        if (subjectId != null && subjectId != 0) 'subject_id': subjectId,
      };

      if (childId != null && childId != 0) {
        queryParameters.addAll({'child_id': childId});
      }

      final response = await Api.get(
        url: childId != null
            ? Api.parentAssignmentReport
            : Api.studentAssignmentReport,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return AssignmentReport.fromJson(Map.from(response['data'] ?? {}));
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<AttendanceReport> getAttendanceReport({
    required int sessionYearId,
    required int month,
    int? childId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'session_year_id': sessionYearId,
        'month': month,
      };

      if (childId != null && childId != 0) {
        queryParameters.addAll({'child_id': childId});
      }

      final response = await Api.get(
        url: childId != null
            ? Api.parentAttendanceReport
            : Api.studentAttendanceReport,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return AttendanceReport.fromJson(Map.from(response['data'] ?? {}));
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<OfflineExamReport> getOfflineExamReport({
    required int sessionYearId,
    int? childId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'session_year_id': sessionYearId,
      };
      if (childId != null && childId != 0) {
        queryParameters.addAll({'child_id': childId});
      }

      final response = await Api.get(
        url: childId != null
            ? Api.parentOfflineExamReport
            : Api.studentOfflineExamReport,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return OfflineExamReport.fromJson(Map.from(response['data'] ?? {}));
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<OnlineExamReport> getOnlineExamReport({
    required int sessionYearId,
    int? childId,
    int? subjectId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'session_year_id': sessionYearId,
        if (subjectId != null && subjectId != 0) 'subject_id': subjectId,
      };
      if (childId != null && childId != 0) {
        queryParameters.addAll({'child_id': childId});
      }

      final response = await Api.get(
        url: childId != null
            ? Api.parentOnlineExamReport
            : Api.studentOnlineExamReport,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return OnlineExamReport.fromJson(Map.from(response['data'] ?? {}));
    } catch (e) {
      throw ApiException(e.toString());
    }
  }
}
