import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SignInState {}

class SignInInitial extends SignInState {}

class SignInInProgress extends SignInState {}

class SignInSuccess extends SignInState {
  SignInSuccess({required this.jwtToken, required this.teacher});
  final String jwtToken;
  final Teacher teacher;
}

class SignInFailure extends SignInState {
  SignInFailure(this.exception);
  final ApiException exception;
}

class SignInCubit extends Cubit<SignInState> {
  SignInCubit(this._authRepository) : super(SignInInitial());
  final AuthRepository _authRepository;

  Future<void> signInUser({
    required String email,
    required String password,
  }) async {
    emit(SignInInProgress());

    try {
      final Map<String, dynamic> result = await _authRepository.signInTeacher(
        email: email,
        password: password,
      );
      NotificationUtility.subscribeOrUnsubscribeToNotificationTopics(
        isUnsubscribe: false,
      );
      emit(
        SignInSuccess(
          jwtToken: result['jwtToken'],
          teacher: result['teacher'] as Teacher,
        ),
      );
    } catch (e) {
      emit(SignInFailure(ApiException.fromException(e)));
    }
  }
}
