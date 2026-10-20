import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UserProfileState {}

class UserProfileInitial extends UserProfileState {}

class UserProfileFetchInProgress extends UserProfileState {}

class UserProfileFetchSuccess extends UserProfileState {
  UserProfileFetchSuccess();
}

class UserProfileFetchFailure extends UserProfileState {
  UserProfileFetchFailure(this.errorMessage);
  final String errorMessage;
}

class UserProfileCubit extends Cubit<UserProfileState> {
  UserProfileCubit(this._authRepository) : super(UserProfileInitial());
  final AuthRepository _authRepository;

  Future<void> fetchAndSetUserProfile() async {
    emit(UserProfileFetchInProgress());
    if (_authRepository.getIsLogIn()) {
      try {
        final Teacher? teacher = await _authRepository.fetchTeacherProfile();
        if (teacher == null) {
          _authRepository.signOutUser();
        } else {
          _authRepository.setTeacherDetails(teacher);
        }
        emit(UserProfileFetchSuccess());
      } catch (e) {
        emit(UserProfileFetchFailure(e.toString()));
      }
    } else {
      emit(UserProfileFetchSuccess());
    }
  }
}
