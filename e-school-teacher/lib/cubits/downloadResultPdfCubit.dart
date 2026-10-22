import 'package:bloc/bloc.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_core/eschool_core.dart';

abstract class DownloadResultPdfState {}

class DownloadResultPdfInitial extends DownloadResultPdfState {}

class DownloadResultPdfDownloadSuccess extends DownloadResultPdfState {
  DownloadResultPdfDownloadSuccess(
    this.filePath,
  );
  String filePath;
}

class DownloadResultPdfDownloadFailure extends DownloadResultPdfState {

  DownloadResultPdfDownloadFailure(this.errorMessage);
  final String errorMessage;
}

class DownloadResultPdfDownloadInProgress extends DownloadResultPdfState {
  DownloadResultPdfDownloadInProgress();
}

class DownloadResultPdfCubit extends Cubit<DownloadResultPdfState> {

  DownloadResultPdfCubit(this._studentRepository)
      : super(DownloadResultPdfInitial());
  final StudentRepository _studentRepository;

  void downloadDownloadResultPdf({
    required int studentId,
    required String fileNamePrefix,
  }) {
    emit(DownloadResultPdfDownloadInProgress());
    _studentRepository
        .downloadExamResultPdf(
      studentId: studentId,
    )
        .then((value) async {
      final String prefix = sanitizeFileName(fileNamePrefix, fallback: '');
      final String filePath = await DeviceFiles.saveDocument(
        value,
        'Exam Results/${prefix}_on_${DateTime.now()}.pdf',
      );
      emit(DownloadResultPdfDownloadSuccess(filePath));
    }).catchError((e) {
      emit(DownloadResultPdfDownloadFailure(e.toString()));
    }).timeout(
      const Duration(seconds: 60),
      onTimeout: () {
        emit(DownloadResultPdfDownloadFailure(''));
      },
    );
  }
}
