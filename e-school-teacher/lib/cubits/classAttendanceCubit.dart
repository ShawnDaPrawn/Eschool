import 'package:eschool_teacher/data/models/attendanceReport.dart';
import 'package:eschool_teacher/data/models/holiday.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';

abstract class ClassAttendanceState {}

class ClassAttendanceInitial extends ClassAttendanceState {}

class ClassAttendanceFetchInProgress extends ClassAttendanceState {}

class ClassAttendanceFetchSuccess extends ClassAttendanceState {

  ClassAttendanceFetchSuccess({
    required this.attendanceReports,
    required this.isHoliday,
    required this.holidayDetails,
    required this.leaveDetails,
  });
  final List<AttendanceReport> attendanceReports;
  final bool isHoliday;
  final Holiday holidayDetails;
  final Map<int, AttendanceLeaveType> leaveDetails;

  bool isOnLeave(int studentId) {
    return leaveDetails.containsKey(studentId);
  }

  AttendanceLeaveType? getLeaveType(int studentId) {
    return leaveDetails[studentId];
  }
}

class ClassAttendanceFetchFailure extends ClassAttendanceState {

  ClassAttendanceFetchFailure(this.errorMessage);
  final String errorMessage;
}

class ClassAttendanceCubit extends Cubit<ClassAttendanceState> {

  ClassAttendanceCubit(this._teacherRepository)
      : super(ClassAttendanceInitial());
  final TeacherRepository _teacherRepository;

  Future<void> fetchAttendanceReports({
    required int classSectionId,
    required DateTime date,
  }) async {
    emit(ClassAttendanceFetchInProgress());
    try {
      final result = await _teacherRepository.getClassAttendanceReports(
        classSectionId: classSectionId,
        date: '${date.year}-${date.month}-${date.day}',
      );

      emit(
        ClassAttendanceFetchSuccess(
          attendanceReports: result['attendanceReports'],
          isHoliday: result['isHoliday'],
          holidayDetails: result['holidayDetails'],
          leaveDetails: result['leaveDetails'] ?? {},
        ),
      );
    } catch (e) {
      emit(ClassAttendanceFetchFailure(e.toString()));
    }
  }
}
