import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:eschool/ui/screens/reports/models/assignmentReport.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AssignmentReportState {}

class AssignmentReportInitial extends AssignmentReportState {}

class AssignmentReportFetchInProgress extends AssignmentReportState {}

class AssignmentReportFetchSuccess extends AssignmentReportState {
  final AssignmentReport assignmentReport;
  AssignmentReportFetchSuccess({required this.assignmentReport});
}

class AssignmentReportFetchFailure extends AssignmentReportState {
  final String errorMessage;
  AssignmentReportFetchFailure({required this.errorMessage});
}

class AssignmentReportCubit extends Cubit<AssignmentReportState> {
  final ReportRepository _reportRepository;

  AssignmentReportCubit(this._reportRepository) : super(AssignmentReportInitial());

  Future<void> fetchAssignmentReport({
    required int sessionYearId,
    int? childId,
    int? subjectId,
  }) async {
    emit(AssignmentReportFetchInProgress());
    try {
      final result = await _reportRepository.getAssignmentReport(
        sessionYearId: sessionYearId,
        childId: childId,
        subjectId: subjectId,
      );
      emit(AssignmentReportFetchSuccess(assignmentReport: result));
    } catch (e) {
      emit(AssignmentReportFetchFailure(errorMessage: e.toString()));
    }
  }
}
