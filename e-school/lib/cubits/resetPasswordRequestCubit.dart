import 'package:equatable/equatable.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class RequestResetPasswordState extends Equatable {}

class RequestResetPasswordInitial extends RequestResetPasswordState {
  @override
  List<Object?> get props => [];
}

class RequestResetPasswordInProgress extends RequestResetPasswordState {
  @override
  List<Object?> get props => [];
}

class RequestResetPasswordSuccess extends RequestResetPasswordState {
  @override
  List<Object?> get props => [];
}

class RequestResetPasswordFailure extends RequestResetPasswordState {
  RequestResetPasswordFailure(this.exception);
  final ApiException exception;

  @override
  List<Object?> get props => [];
}

class RequestResetPasswordCubit extends Cubit<RequestResetPasswordState> {
  RequestResetPasswordCubit(this._authRepository)
    : super(RequestResetPasswordInitial());
  final AuthRepository _authRepository;

  Future<void> requestResetPassword({
    required String grNumber,
    required DateTime dob,
  }) async {
    emit(RequestResetPasswordInProgress());
    try {
      await _authRepository.resetPasswordRequest(grNumber: grNumber, dob: dob);
      emit(RequestResetPasswordSuccess());
    } catch (e) {
      emit(RequestResetPasswordFailure(ApiException.fromException(e)));
    }
  }
}
