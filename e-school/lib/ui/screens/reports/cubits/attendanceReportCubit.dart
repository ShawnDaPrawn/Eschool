import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool/ui/screens/reports/models/attendanceReport.dart';

abstract class AttendanceReportState {}

class AttendanceReportInitial extends AttendanceReportState {}

class AttendanceReportFetchInProgress extends AttendanceReportState {}

class AttendanceReportFetchSuccess extends AttendanceReportState {
  final AttendanceReport attendanceData;
  AttendanceReportFetchSuccess({required this.attendanceData});
}

class AttendanceReportFetchFailure extends AttendanceReportState {
  final String errorMessage;
  AttendanceReportFetchFailure({required this.errorMessage});
}

class AttendanceReportCubit extends Cubit<AttendanceReportState> {
  final ReportRepository _reportRepository;

  AttendanceReportCubit(this._reportRepository) : super(AttendanceReportInitial());

  Future<void> fetchAttendanceReport({
    required int sessionYearId,
    required int month,
    int? childId,
  }) async {
    emit(AttendanceReportFetchInProgress());
    try {
      final result = await _reportRepository.getAttendanceReport(
        sessionYearId: sessionYearId,
        childId: childId,
        month: month,
      );
      emit(AttendanceReportFetchSuccess(attendanceData: result));
    } catch (e) {
      emit(AttendanceReportFetchFailure(errorMessage: e.toString()));
    }
  }
}
