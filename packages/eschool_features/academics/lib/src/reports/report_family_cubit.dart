import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'report_repository.dart';

String reportFailure(Object error) =>
    error is ApiException && error.isApiMessage
    ? error.errorMessage
    : 'Could not load reports. Check your connection and access, then try again.';

class FamilyReportsState {
  const FamilyReportsState({
    this.reports = const [],
    this.page = 0,
    this.lastPage = 1,
    this.busy = false,
    this.error,
  });
  final List<AcademicReportFamilyCard> reports;
  final int page, lastPage;
  final bool busy;
  final String? error;
}

class FamilyReportsCubit extends Cubit<FamilyReportsState> {
  FamilyReportsCubit(this.repository, this.learner)
    : super(const FamilyReportsState());
  final ReportRepository repository;
  final int learner;
  int _generation = 0;
  Future<void> load({bool more = false}) async {
    if (more && (state.busy || state.page >= state.lastPage)) return;
    final generation = ++_generation;
    final previous = more ? state.reports : <AcademicReportFamilyCard>[];
    final page = more ? state.page + 1 : 1;
    emit(
      FamilyReportsState(
        reports: previous,
        page: more ? state.page : 0,
        lastPage: state.lastPage,
        busy: true,
      ),
    );
    try {
      final data = await repository.family(learner, page: page);
      if (isClosed || generation != _generation) return;
      if (data.learnerId != learner || data.page != page) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      final reports = {
        for (final row in [...previous, ...data.reports]) row.id: row,
      }.values.toList();
      emit(
        FamilyReportsState(
          reports: reports,
          page: data.page,
          lastPage: data.lastPage,
        ),
      );
    } catch (error) {
      if (!isClosed && generation == _generation) {
        final revoked =
            error is ApiException && [401, 403, 404].contains(error.statusCode);
        emit(
          FamilyReportsState(
            reports: revoked ? const [] : previous,
            page: revoked ? 0 : (more ? page - 1 : 0),
            lastPage: revoked ? 1 : state.lastPage,
            error: reportFailure(error),
          ),
        );
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    return super.close();
  }
}

class ReportPdfState {
  const ReportPdfState({this.bytes, this.error, this.busy = false});
  final Uint8List? bytes;
  final String? error;
  final bool busy;
}

class ReportPdfCubit extends Cubit<ReportPdfState> {
  ReportPdfCubit(
    this.repository, {
    required this.card,
    this.learner,
    this.batch,
  }) : assert((learner == null) != (batch == null)),
       super(const ReportPdfState());
  final ReportRepository repository;
  final int card;
  final int? learner, batch;
  int _generation = 0;
  void clear() {
    _generation++;
    if (!isClosed) emit(const ReportPdfState());
  }

  Future<void> load() async {
    final generation = ++_generation;
    emit(const ReportPdfState(busy: true));
    try {
      final bytes = learner != null
          ? await repository.familyPdf(card, learner!)
          : await repository.staffPdf(batch!, card);
      if (isClosed || generation != _generation) return;
      emit(ReportPdfState(bytes: bytes));
      if (learner != null) {
        try {
          await repository.read(card);
        } catch (_) {}
      }
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(ReportPdfState(error: reportFailure(error)));
      }
    }
  }

  @override
  Future<void> close() {
    clear();
    return super.close();
  }
}
