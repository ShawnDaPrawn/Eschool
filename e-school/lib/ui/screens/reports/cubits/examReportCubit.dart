import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool/ui/screens/reports/models/offlineExamReport.dart';
import 'package:eschool/ui/screens/reports/models/onlineExamReport.dart';

abstract class ExamReportState {}

class ExamReportInitial extends ExamReportState {}

class ExamReportFetchInProgress extends ExamReportState {}

class OfflineExamReportFetchSuccess extends ExamReportState {
  final OfflineExamReport examData;
  OfflineExamReportFetchSuccess({required this.examData});
}

class OnlineExamReportFetchSuccess extends ExamReportState {
  final OnlineExamReport examData;
  OnlineExamReportFetchSuccess({required this.examData});
}

class ExamReportFetchFailure extends ExamReportState {
  final String errorMessage;
  ExamReportFetchFailure({required this.errorMessage});
}

class ExamReportCubit extends Cubit<ExamReportState> {
  final ReportRepository _reportRepository;

  ExamReportCubit(this._reportRepository) : super(ExamReportInitial());

  Future<void> fetchOfflineExamReport({
    required int sessionYearId,
    int? childId,
  }) async {
    emit(ExamReportFetchInProgress());
    try {
      final result = await _reportRepository.getOfflineExamReport(
        sessionYearId: sessionYearId,
        childId: childId,
      );
      emit(OfflineExamReportFetchSuccess(examData: result));
    } catch (e) {
      emit(ExamReportFetchFailure(errorMessage: e.toString()));
    }
  }

  Future<void> fetchOnlineExamReport({
    required int sessionYearId,
    int? childId,
    int? subjectId,
  }) async {
    emit(ExamReportFetchInProgress());
    try {
      final result = await _reportRepository.getOnlineExamReport(
        sessionYearId: sessionYearId,
        childId: childId,
        subjectId: subjectId,
      );
      emit(OnlineExamReportFetchSuccess(examData: result));
    } catch (e) {
      emit(ExamReportFetchFailure(errorMessage: e.toString()));
    }
  }
}
