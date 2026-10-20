// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/models/fees.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/utils/api.dart';

abstract class FeesPaymentState {}

class FeesPaymentInitial extends FeesPaymentState {}

class FeesPaymentFetchSuccess extends FeesPaymentState {
  FeesPaymentFetchSuccess(this.paymentGatewayDetails);
  final Map paymentGatewayDetails;
}

class FeesPaymentFetchFailure extends FeesPaymentState {
  FeesPaymentFetchFailure(this.exception);
  final ApiException exception;
}

class FeesPaymentFetchInProgress extends FeesPaymentState {}

class FeesPaymentCubit extends Cubit<FeesPaymentState> {
  FeesPaymentCubit(this._studentRepository) : super(FeesPaymentInitial());
  final StudentRepository _studentRepository;

  void addFeesTransaction({
    int? childId,
    required double transactionAmount,
    required int typeOfFee,
    required bool isFullyPaid,
    required double? paidDueCharges,
    double? compulsoryAmountPaid,
    double? dueChargesPaid,
    required int feesType, //0 = compulsory, 1 = installments, 2 = optional
    required List<FeesData> selectedFees,
    required int paymentMethod,
  }) {
    emit(FeesPaymentFetchInProgress());
    _studentRepository
        .addFeesTransaction(
          transactionAmount: transactionAmount,
          childId: childId,
          typeOfFee: typeOfFee,
          isFullyPaid: isFullyPaid,
          paidDueCharges: paidDueCharges,
          compulsoryAmountPaid: compulsoryAmountPaid,
          dueChargesPaid: dueChargesPaid,
          feesType: feesType,
          selectedFees: selectedFees,
          paymentMethod: paymentMethod,
        )
        .then((value) => emit(FeesPaymentFetchSuccess(value)))
        .catchError((e) {
          emit(FeesPaymentFetchFailure(ApiException.fromException(e)));
        });
  }
}
