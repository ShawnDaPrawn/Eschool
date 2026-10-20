import 'package:dio/dio.dart';
import 'package:eschool/data/models/leave.dart';
import 'package:eschool/utils/api.dart';
import 'package:intl/intl.dart';

class LeaveRepository {
  Future<void> addLeaveRequest({
    required String reason,
    required List<LeaveDateWithType> leaveDetails,
    required bool isParent,
    int? childId,
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
        if (isParent) 'child_id': childId,
      },
      url: isParent ? Api.parentAddLeave : Api.studentApplyLeave,
      useAuthToken: true,
    );
  }

  Future<
    ({List<Leave> leaves, double leavesTaken, double monthlyAllowedLeaves})
  >
  getLeaves({
    int? month,
    int? sessionYearId,
    required bool isParent,
    int? status,
    int? childId,
  }) async {
    try {
      final result = await Api.post(
        body: {
          if (month != null) 'month': month,
          if (sessionYearId != null) 'session_year_id': sessionYearId,
          if (status != null) 'status': status,
          if (isParent) 'child_id': childId,
        }, //0- pending , 1-approved , 2-rejected
        url: isParent ? Api.parentGetLeaves : Api.studentGetLeaveList,
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

  Future<void> deleteLeave({
    required int leaveId,
    required bool isParent,
    int? childId,
  }) async {
    await Api.post(
      body: {'leave_id': leaveId, if (isParent) 'child_id': childId},
      url: isParent ? Api.parentDeleteLeave : Api.studentDeleteLeave,
      useAuthToken: true,
    );
  }
}
