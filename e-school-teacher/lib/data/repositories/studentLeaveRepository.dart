import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/utils/api.dart';

class StudentLeaveRepository {
  Future<({List<StudentLeave> leaves, int totalRequestedLeaves})>
  getStudentLeaveRequest({
    required String classSectionId,
    required String? month,
    required String sessionYearId,
  }) async {
    try {
      final result = await Api.post(
        body: {
          'class_section_id': classSectionId,
          if (month != null) 'month': month,
          'session_year_id': sessionYearId,
          'status': 0,
        },
        url: Api.getStudentLeaveList,
        useAuthToken: true,
      );
      return (
        leaves: ((result['data']['leave_details'] ?? []) as List)
            .map<StudentLeave>(
              (leave) => StudentLeave.fromJson(Map.from(leave)),
            )
            .toList(),
        totalRequestedLeaves: int.parse(
          result['data']['total_leave_requests'].toString(),
        ),
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> updateStudentLeaveStatus({
    required String leaveId,
    required String status,
    String? reason,
  }) async {
    await Api.post(
      body: {
        'leave_id': leaveId,
        'status': status,
        if (reason != null) 'reason_of_rejection': reason,
      },
      url: Api.updateStudentLeaveStatus,
      useAuthToken: true,
    );
  }
}
