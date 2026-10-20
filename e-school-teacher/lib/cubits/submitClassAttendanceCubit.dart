import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';

abstract class SubmitClassAttendanceState {}

class SubmitClassAttendanceInitial extends SubmitClassAttendanceState {}

class SubmitClassAttendanceInProgress extends SubmitClassAttendanceState {}

class SubmitClassAttendanceSuccess extends SubmitClassAttendanceState {}

class SubmitClassAttendanceFailure extends SubmitClassAttendanceState {
  SubmitClassAttendanceFailure(this.exception);
  final ApiException exception;
}

class SubmitClassAttendanceCubit extends Cubit<SubmitClassAttendanceState> {
  SubmitClassAttendanceCubit(this._teacherRepository)
    : super(SubmitClassAttendanceInitial());
  final TeacherRepository _teacherRepository;

  Future<void> submitAttendance({
    required DateTime dateTime,
    required int classSectionId,
    required List<Map<int, AttendanceType?>> attendanceReport,
  }) async {
    emit(SubmitClassAttendanceInProgress());
    try {
      await _teacherRepository.submitClassAttendance(
        classSectionId: classSectionId,
        date: '${dateTime.year}-${dateTime.month}-${dateTime.day}',
        attendance: attendanceReport
            .where((report) => report.values.first != null)
            .map(
              (report) => {
                'student_id': report.keys.first,
                'type': report.values.first!.value,
              },
            )
            .toList(),
      );
      emit(SubmitClassAttendanceSuccess());
    } catch (e) {
      emit(SubmitClassAttendanceFailure(ApiException.fromException(e)));
    }
  }
}
