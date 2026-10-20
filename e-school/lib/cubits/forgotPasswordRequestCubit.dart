import 'package:equatable/equatable.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ForgotPasswordRequestState extends Equatable {}

class ForgotPasswordRequestInitial extends ForgotPasswordRequestState {
  @override
  List<Object?> get props => [];
}

class ForgotPasswordRequestInProgress extends ForgotPasswordRequestState {
  @override
  List<Object?> get props => [];
}

class ForgotPasswordRequestSuccess extends ForgotPasswordRequestState {
  @override
  List<Object?> get props => [];
}

class ForgotPasswordRequestFailure extends ForgotPasswordRequestState {
  ForgotPasswordRequestFailure(this.exception);
  final ApiException exception;

  @override
  List<Object?> get props => [];
}

class ForgotPasswordRequestCubit extends Cubit<ForgotPasswordRequestState> {
  ForgotPasswordRequestCubit(this._authRepository)
    : super(ForgotPasswordRequestInitial());
  final AuthRepository _authRepository;

  Future<void> requestforgotPassword({required String email}) async {
    emit(ForgotPasswordRequestInProgress());
    try {
      await _authRepository.forgotPassword(email: email);
      emit(ForgotPasswordRequestSuccess());
    } catch (e) {
      emit(ForgotPasswordRequestFailure(ApiException.fromException(e)));
    }
  }
}
