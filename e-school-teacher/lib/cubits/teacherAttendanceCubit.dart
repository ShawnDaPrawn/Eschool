import 'package:eschool_teacher/data/models/teacherAttendance.dart';
import 'package:eschool_teacher/data/repositories/teacherAttendanceRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TeacherAttendanceState {}

class TeacherAttendanceInitial extends TeacherAttendanceState {}

class TeacherAttendanceFetchInProgress extends TeacherAttendanceState {}

class TeacherAttendanceFetchSuccess extends TeacherAttendanceState {
  final TeacherAttendanceReport report;

  TeacherAttendanceFetchSuccess({required this.report});
}

class TeacherAttendanceFetchFailure extends TeacherAttendanceState {
  final String errorMessage;

  TeacherAttendanceFetchFailure({required this.errorMessage});
}

class TeacherAttendanceCubit extends Cubit<TeacherAttendanceState> {
  final TeacherAttendanceRepository _teacherAttendanceRepository;

  TeacherAttendanceCubit(this._teacherAttendanceRepository)
      : super(TeacherAttendanceInitial());

  Future<void> fetchTeacherAttendance({
    required int month,
    required int year,
  }) async {
    emit(TeacherAttendanceFetchInProgress());
    try {
      final TeacherAttendanceReport report =
          await _teacherAttendanceRepository.fetchTeacherAttendance(
        month: month,
        year: year,
      );
      emit(TeacherAttendanceFetchSuccess(report: report));
    } catch (e) {
      emit(TeacherAttendanceFetchFailure(errorMessage: e.toString()));
    }
  }
}
