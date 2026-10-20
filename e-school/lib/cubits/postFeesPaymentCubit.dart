// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/utils/stripeService.dart';

abstract class PostFeesPaymentState {}

class PostFeesPaymentInitial extends PostFeesPaymentState {}

class PostFeesPaymentSuccess extends PostFeesPaymentState {}

class PostFeesPaymentFailure extends PostFeesPaymentState {

  PostFeesPaymentFailure(this.errorMessage);
  final String errorMessage;
}

class PostFeesPaymentInProgress extends PostFeesPaymentState {}

class PostFeesPaymentCubit extends Cubit<PostFeesPaymentState> {

  PostFeesPaymentCubit(this._studentRepository)
      : super(PostFeesPaymentInitial());
  final StudentRepository _studentRepository;

  Future<void> storeFees({
    required int? status,
    required String transactionId,
    required bool verifyStripePaymentIntent, int? childId,
    String? paymentIntentId,
    String? paymentId,
    String? paymentSignature,
  }) async {
    emit(PostFeesPaymentInProgress());
    try {
      if (status == 1 || verifyStripePaymentIntent) {
        // 1 is success when calling this function
        if (verifyStripePaymentIntent) {
          final paymentIntentStatus =
              await _studentRepository.confirmStripePayment(
            paymentIntentId: paymentIntentId ?? '',
            isStudentLoggedIn: childId == null,
          );

          if (paymentIntentStatus !=
              StripeService.paymentIntentSuccessResponse) {
            throw Exception('Payment failed');
          }
        }
        await _studentRepository.storeFees(
          childId: childId,
          transactionId: transactionId,
          paymentId: paymentId,
          paymentSignature: paymentSignature,
        );
        emit(PostFeesPaymentSuccess());
      } else {
        await _studentRepository.failPaymentTransaction(
          transactionId: transactionId,
          isStudentPayingFees: childId ==
              null, //child id won't be there if student is paying their own fees
        );
        emit(PostFeesPaymentFailure('Payment failed'));
      }
    } catch (e) {
      await _studentRepository.failPaymentTransaction(
        transactionId: transactionId,
        isStudentPayingFees: childId ==
            null, //child id won't be there if student is paying their own fees
      );
      emit(PostFeesPaymentFailure(e.toString()));
    }
  }
}
