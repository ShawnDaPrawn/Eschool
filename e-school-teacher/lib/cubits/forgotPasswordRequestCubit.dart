import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ForgotPasswordRequestState {}

class ForgotPasswordRequestInitial extends ForgotPasswordRequestState {}

class ForgotPasswordRequestInProgress extends ForgotPasswordRequestState {}

class ForgotPasswordRequestSuccess extends ForgotPasswordRequestState {}

class ForgotPasswordRequestFailure extends ForgotPasswordRequestState {
  ForgotPasswordRequestFailure(this.exception);
  final ApiException exception;
}

class ForgotPasswordRequestCubit extends Cubit<ForgotPasswordRequestState> {
  ForgotPasswordRequestCubit(this._authRepository)
    : super(ForgotPasswordRequestInitial());
  final AuthRepository _authRepository;

  Future<void> requestForgotPassword({required String email}) async {
    emit(ForgotPasswordRequestInProgress());
    try {
      await _authRepository.forgotPassword(email: email);
      emit(ForgotPasswordRequestSuccess());
    } catch (e) {
      emit(ForgotPasswordRequestFailure(ApiException.fromException(e)));
    }
  }
}
