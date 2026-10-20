// ignore: depend_on_referenced_packages
import 'dart:io';

// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:path_provider/path_provider.dart';

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

  void downloadAcademicCalendarPdf(String currentSessionYearName) {
    if (state is AcademicCalendarPdfDownloadInProgress) {
      return;
    }
    emit(AcademicCalendarPdfDownloadInProgress());
    _systemRepository
        .downloadAcademicCalendarPDF()
        .then((value) async {
          String filePath = '';
          final path = (await getApplicationDocumentsDirectory()).path;
          filePath = '$path/Academic Calendar/$currentSessionYearName.pdf';

          final File file = File(filePath);
          if (!file.existsSync()) {
            await file.create(recursive: true);
          }
          await file.writeAsBytes(value);
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
