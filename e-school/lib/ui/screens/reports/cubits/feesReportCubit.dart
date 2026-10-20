import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool/ui/screens/reports/models/feesReport.dart';

abstract class FeesReportState {}

class FeesReportInitial extends FeesReportState {}

class FeesReportFetchInProgress extends FeesReportState {}

class FeesReportFetchSuccess extends FeesReportState {
  final FeesReport feesData;
  FeesReportFetchSuccess({required this.feesData});
}

class FeesReportFetchFailure extends FeesReportState {
  final String errorMessage;
  FeesReportFetchFailure({required this.errorMessage});
}

class FeesReportCubit extends Cubit<FeesReportState> {
  final ReportRepository _reportRepository;

  FeesReportCubit(this._reportRepository) : super(FeesReportInitial());

  void fetchFeesReport({
    required int sessionYearId,
    int? childId,
  }) async {
    emit(FeesReportFetchInProgress());
    try {
      final result = await _reportRepository.getFeesReport(
        sessionYearId: sessionYearId,
        childId: childId,
      );
      emit(FeesReportFetchSuccess(feesData: result));
    } catch (e) {
      emit(FeesReportFetchFailure(errorMessage: e.toString()));
    }
  }
}
