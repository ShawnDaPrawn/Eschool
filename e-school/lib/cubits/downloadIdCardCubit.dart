// ignore: depend_on_referenced_packages
import 'dart:io';

// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:path_provider/path_provider.dart';

abstract class DownloadIdCardState {}

class DownloadIdCardInitial extends DownloadIdCardState {}

class DownloadIdCardSuccess extends DownloadIdCardState {
  DownloadIdCardSuccess({
    required this.successMessageKey,
    required this.filePath,
  });
  final String successMessageKey;
  final String filePath;
}

class DownloadIdCardFailure extends DownloadIdCardState {
  DownloadIdCardFailure(this.exception);
  final ApiException exception;
}

class DownloadIdCardInProgress extends DownloadIdCardState {}

class DownloadIdCardCubit extends Cubit<DownloadIdCardState> {
  DownloadIdCardCubit(this._systemRepository) : super(DownloadIdCardInitial());
  final SystemRepository _systemRepository;

  void downloadIdCard({String? childId, String? childName}) async {
    if (state is DownloadIdCardInProgress) {
      return;
    }
    emit(DownloadIdCardInProgress());

    _systemRepository
        .downloadIdCardPDF(childId: childId)
        .then((value) async {
          final path = (await getApplicationDocumentsDirectory()).path;
          String filePath = '$path/ID_Card/$childName ID Card.pdf';

          final File file = File(filePath);
          await file.create(recursive: true);

          await file.writeAsBytes(value);
          emit(
            DownloadIdCardSuccess(
              successMessageKey: fileDownloadedSuccessfullyKey,
              filePath: filePath,
            ),
          );
        })
        .catchError((e) {
          emit(DownloadIdCardFailure(ApiException.fromException(e)));
        })
        .timeout(
          const Duration(seconds: 60),
          onTimeout: () {
            emit(DownloadIdCardFailure(ApiException.fromException('')));
          },
        );
  }
}
