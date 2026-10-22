// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/models/paidFees.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/labelKeys.dart';

abstract class FeesReceiptState {}

class FeesReceiptInitial extends FeesReceiptState {}

class FeesReceiptDownloadSuccess extends FeesReceiptState {
  FeesReceiptDownloadSuccess({
    required this.successMessageKey,
    required this.filePath,
    required this.fees,
  });
  final String successMessageKey;
  final String filePath;
  final PaidFees? fees;
}

class FeesReceiptDownloadFailure extends FeesReceiptState {
  FeesReceiptDownloadFailure(this.exception);
  final ApiException exception;
}

class FeesReceiptDownloadInProgress extends FeesReceiptState {
  FeesReceiptDownloadInProgress(this.currentlyDownloading);
  final PaidFees? currentlyDownloading;
}

class FeesReceiptCubit extends Cubit<FeesReceiptState> {
  FeesReceiptCubit(this._studentRepository) : super(FeesReceiptInitial());
  final StudentRepository _studentRepository;

  void downloadFeesReceipt({
    required String fileNamePrefix,
    int? feesPaidId,
    List<PaidFees>? receiptList,
  }) {
    int? currentIndex;
    if (receiptList != null) {
      currentIndex = receiptList.indexWhere(
        (element) => element.id == feesPaidId,
      );

      emit(FeesReceiptDownloadInProgress(receiptList[currentIndex]));
    } else {
      emit(FeesReceiptDownloadInProgress(null));
    }
    _studentRepository
        .downloadFeesReceipt(feesPaidId: feesPaidId)
        .then((value) async {
          final String prefix = sanitizeFileName(fileNamePrefix, fallback: '');
          final String filePath = await DeviceFiles.saveDocument(
            value,
            'Fees Receipts/${prefix}_fees_payment_receipt_on_${DateTime.now()}.pdf',
          );
          emit(
            FeesReceiptDownloadSuccess(
              successMessageKey: feesRecieptDownloadedKey,
              filePath: filePath,
              fees: currentIndex != null ? receiptList![currentIndex] : null,
            ),
          );
        })
        .catchError((e) {
          emit(FeesReceiptDownloadFailure(ApiException.fromException(e)));
        })
        .timeout(
          const Duration(seconds: 60),
          onTimeout: () {
            emit(FeesReceiptDownloadFailure(ApiException.fromException('')));
          },
        );
  }
}
