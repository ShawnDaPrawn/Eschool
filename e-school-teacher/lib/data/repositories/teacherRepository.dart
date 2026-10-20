import 'package:eschool_teacher/data/models/attendanceReport.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/customNotification.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/models/gallery.dart';
import 'package:eschool_teacher/data/models/holiday.dart';
import 'package:eschool_teacher/data/models/staffLeave.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';

class TeacherRepository {
  Future<Map<String, dynamic>> dashboard() async {
    try {
      final result = await Api.get(url: Api.dashboard, useAuthToken: true);

      return {
        'primaryClass': result['data']['class_teacher'].isEmpty
            ? null
            : (result['data']['class_teacher'] as List?)
                      ?.map((e) => ClassSectionDetails.fromJson(Map.from(e)))
                      .toList() ??
                  [],
        'classes':
            (result['data']['other_classes'] as List?)
                ?.map((e) => ClassSectionDetails.fromJson(Map.from(e)))
                .toList() ??
            [],
        'pendingLeaveRequests':
            (result['data']['student_leave_request'] as List?)
                ?.map((e) => StudentLeave.fromJson(Map.from(e)))
                .toList() ??
            [],
        'todaysTimetable':
            (result['data']['timetable'] as List?)
                ?.map((e) => TimeTableSlot.fromJson(Map.from(e)))
                .toList() ??
            [],
        'upcomingExams':
            (result['data']['upcoming_exams'] as List?)
                ?.map((e) => Exam.fromExamJson(Map.from(e)))
                .toList() ??
            [],
        'todayStaffLeave':
            (result['data']?['staff_leaves']?['today'] as List?)
                ?.map((e) => StaffLeave.fromJson(Map.from(e)))
                .toList() ??
            [],
        'tomorrowStaffLeave':
            (result['data']?['staff_leaves']?['tomorrow'] as List?)
                ?.map((e) => StaffLeave.fromJson(Map.from(e)))
                .toList() ??
            [],
        'upcomingStaffLeave':
            (result['data']?['staff_leaves']?['upcoming'] as List?)
                ?.map((e) => StaffLeave.fromJson(Map.from(e)))
                .toList() ??
            [],
        'upcomingEvents':
            (result['data']['events'] as List?)
                ?.map((e) => Event.fromJson(Map.from(e)))
                .toList() ??
            [],
        'gallery': result['data']['gallery'] == null
            ? null
            : Gallery.fromJson(result['data']['gallery']),
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<Subject>> subjectsByClassSection(int classSectionId) async {
    try {
      final result = await Api.get(
        url: Api.getSubjectByClassSection,
        useAuthToken: true,
        queryParameters: {'class_section_id': classSectionId},
      );
      return (result['data'] as List)
          .map(
            (subjectJson) => Subject.fromJson(Map.from(subjectJson['subject'])),
          )
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> getClassAttendanceReports({
    required int classSectionId,
    required String date,
  }) async {
    try {
      final result = await Api.get(
        url: Api.getAttendance,
        useAuthToken: true,
        queryParameters: {'class_section_id': classSectionId, 'date': date},
      );
      final data = result['data'];
      return {
        'attendanceReports':
            (data['attendance_reports'] as List?)
                ?.map(
                  (attendanceReport) =>
                      AttendanceReport.fromJson(attendanceReport),
                )
                .toList() ??
            [],
        'isHoliday': data['is_holiday'],
        'holidayDetails': Holiday.fromJson(
          Map.from(
            data['holiday'] == null ? {} : (data['holiday'] as List).first,
          ),
        ),
        'leaveDetails': data['leave_details'] is List
            ? (data['leave_details'] as List)
                  .fold<Map<int, AttendanceLeaveType>>({}, (map, leave) {
                    final leaveType = AttendanceLeaveType.fromValue(
                      leave['leave_type'] as int?,
                    );
                    if (leaveType != null) {
                      map[leave['student_id'] as int] = leaveType;
                    }
                    return map;
                  })
            : <int, AttendanceLeaveType>{},
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> submitClassAttendance({
    required int classSectionId,
    required String date,
    required List<Map<String, dynamic>> attendance,
  }) async {
    await Api.post(
      url: Api.submitAttendance,
      useAuthToken: true,
      body: {
        'class_section_id': classSectionId,
        'date': date,
        'attendance': attendance,
      },
    );
  }

  Future<List<TimeTableSlot>> fetchTimeTable() async {
    try {
      final result = await Api.get(url: Api.timeTable, useAuthToken: true);

      return (result['data'] as List)
          .map(
            (timetableSlot) => TimeTableSlot.fromJson(Map.from(timetableSlot)),
          )
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> updateTimeTableLink({
    required String timetableSlotId,
    required String? linkCustomUrl,
    required String? linkName,
  }) async {
    await Api.post(
      body: {
        'timetable_id': timetableSlotId,
        'live_class_link': linkCustomUrl ?? '',
        'link_name': linkName ?? '',
      },
      url: Api.updateTimetableLink,
      useAuthToken: true,
    );
  }

  //get custom notifications for parent
  Future<Map<String, dynamic>> fetchNotifications({required int page}) async {
    try {
      final response = await Api.get(
        url: Api.getNotifications,
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
