import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/attendanceDay.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AttendanceState extends Equatable {}

class AttendanceInitial extends AttendanceState {
  @override
  List<Object?> get props => [];
}

class AttendanceFetchInProgress extends AttendanceState {
  @override
  List<Object?> get props => [];
}

class AttendanceFetchSuccess extends AttendanceState {

  AttendanceFetchSuccess({
    required this.attendanceReport,
  });
  final AttendanceReport attendanceReport;
  @override
  List<Object?> get props => [attendanceReport];
}

class AttendanceFetchFailure extends AttendanceState {

  AttendanceFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class AttendanceCubit extends Cubit<AttendanceState> {

  AttendanceCubit(this._studentRepository) : super(AttendanceInitial());
  final StudentRepository _studentRepository;

  void fetchAttendance({
    required int month,
    required int year,
    required bool useParentApi,
    int? childId,
  }) {
    emit(AttendanceFetchInProgress());

    _studentRepository
        .fetchAttendance(
          month: month,
          year: year,
          childId: childId ?? 0,
          useParentApi: useParentApi,
        )
        .then(
          (result) => emit(
            AttendanceFetchSuccess(
              attendanceReport: result,
            ),
          ),
        )
        .catchError((e) => emit(AttendanceFetchFailure(e.toString())));
  }
}
