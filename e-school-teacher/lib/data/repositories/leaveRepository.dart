import 'package:dio/dio.dart';
import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:intl/intl.dart';

class LeaveRepository {
  Future<void> addLeaveRequest({
    required String reason,
    required List<LeaveDateWithType> leaveDetails,
    List<String> filePaths = const [],
  }) async {
    final List<MultipartFile> files = [];
    for (final filePath in filePaths) {
      files.add(await MultipartFile.fromFile(filePath));
    }
    await Api.post(
      body: {
        'reason': reason,
        'leave_details': leaveDetails
            .map(
              (e) => {
                'date': DateFormat('yyyy-MM-dd').format(e.date),
                'type': e.type.getAPIType(),
              },
            )
            .toList(),
        if (files.isNotEmpty) 'files': files,
      },
      url: Api.addLeaveRequest,
      useAuthToken: true,
    );
  }

  Future<
    ({List<Leave> leaves, double leavesTaken, double monthlyAllowedLeaves})
  >
  getLeaves({required int sessionYearId, required int month}) async {
    try {
      final result = await Api.post(
        body: {'session_year_id': sessionYearId, 'month': month},
        url: Api.getLeaves,
        useAuthToken: true,
      );

      return (
        leaves: ((result['data']['leave_details'] ?? []) as List)
            .map<Leave>((event) => Leave.fromJson(Map.from(event)))
            .toList(),
        leavesTaken:
            double.tryParse(
              result['data']?['taken_leaves']?.toString() ?? '0',
            ) ??
            0,
        monthlyAllowedLeaves:
            double.tryParse(
              result['data']?['monthly_allowed_leaves']?.toString() ?? '0',
            ) ??
            0,
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> deleteLeave({required int leaveId}) async {
    await Api.post(
      body: {'leave_id': leaveId},
      url: Api.deleteLeave,
      useAuthToken: true,
    );
  }
}
