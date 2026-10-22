// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/labelKeys.dart';

abstract class AcademicCalendarPdfDownloadState {}

class AcademicCalendarPdfDownloadInitial
    extends AcademicCalendarPdfDownloadState {}

class AcademicCalendarPdfDownloadSuccess
    extends AcademicCalendarPdfDownloadState {
  AcademicCalendarPdfDownloadSuccess({
    required this.successMessageKey,
    required this.filePath,
  });
  final String successMessageKey;
  final String filePath;
}

class AcademicCalendarPdfDownloadFailure
    extends AcademicCalendarPdfDownloadState {
  AcademicCalendarPdfDownloadFailure(this.exception);
  final ApiException exception;
}

class AcademicCalendarPdfDownloadInProgress
    extends AcademicCalendarPdfDownloadState {
  AcademicCalendarPdfDownloadInProgress();
}

class AcademicCalendarPdfDownloadCubit
    extends Cubit<AcademicCalendarPdfDownloadState> {
  AcademicCalendarPdfDownloadCubit(this._systemRepository)
    : super(AcademicCalendarPdfDownloadInitial());
  final SystemRepository _systemRepository;

  void downloadAcademicCalendarPdf(
    String currentSessionYearName, {
    String? childId,
  }) {
    if (state is AcademicCalendarPdfDownloadInProgress) {
      return;
    }
    emit(AcademicCalendarPdfDownloadInProgress());
    _systemRepository
        .downloadAcademicCalendarPDF(childId: childId)
        .then((value) async {
          final String fileName = sanitizeFileName(
            currentSessionYearName,
            fallback: 'Academic Calendar',
          );
          final String filePath = await DeviceFiles.saveDocument(
            value,
            'Academic Calendar/$fileName.pdf',
          );
          emit(
            AcademicCalendarPdfDownloadSuccess(
              successMessageKey: fileDownloadedSuccessfullyKey,
              filePath: filePath,
            ),
          );
        })
        .catchError((e) {
          emit(
            AcademicCalendarPdfDownloadFailure(ApiException.fromException(e)),
          );
        })
        .timeout(
          const Duration(seconds: 60),
          onTimeout: () {
            emit(
              AcademicCalendarPdfDownloadFailure(
                ApiException.fromException(''),
              ),
            );
          },
        );
  }
}
