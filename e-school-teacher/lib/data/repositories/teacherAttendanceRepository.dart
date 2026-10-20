import 'package:eschool_teacher/data/models/teacherAttendance.dart';
import 'package:eschool_teacher/utils/api.dart';

class TeacherAttendanceRepository {
  Future<TeacherAttendanceReport> fetchTeacherAttendance({
    required int month,
    required int year,
  }) async {
    try {
      final result = await Api.get(
        url: Api.getTeacherAttendanceReport,
        useAuthToken: true,
        queryParameters: {'month': month, 'year': year},
      );

      return TeacherAttendanceReport.fromJson(
        Map<String, dynamic>.from(result['data'] ?? {}),
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }
}
