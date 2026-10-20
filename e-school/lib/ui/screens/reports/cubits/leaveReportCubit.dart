import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:eschool/ui/screens/reports/models/leaveReport.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class LeaveReportState {}

class LeaveReportInitial extends LeaveReportState {}

class LeaveReportFetchInProgress extends LeaveReportState {}

class LeaveReportFetchSuccess extends LeaveReportState {
  final LeaveReport leaveReport;

  LeaveReportFetchSuccess({required this.leaveReport});
}

class LeaveReportFetchFailure extends LeaveReportState {
  final String errorMessage;

  LeaveReportFetchFailure({required this.errorMessage});
}

class LeaveReportCubit extends Cubit<LeaveReportState> {
  final ReportRepository _reportRepository;

  LeaveReportCubit(this._reportRepository) : super(LeaveReportInitial());

  void fetchLeaveReport({
    required int sessionYearId,
    int? childId,
  }) {
    emit(LeaveReportFetchInProgress());
    _reportRepository
        .getLeaveReport(
      sessionYearId: sessionYearId,
      childId: childId,
    )
        .then((result) {
      emit(LeaveReportFetchSuccess(leaveReport: result));
    }).catchError((e) {
      emit(LeaveReportFetchFailure(errorMessage: e.toString()));
    });
  }
}
