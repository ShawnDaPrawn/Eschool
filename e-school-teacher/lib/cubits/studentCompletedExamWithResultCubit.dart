import 'package:eschool_teacher/data/models/studentResult.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class StudentCompletedExamWithResultState {}

class StudentCompletedExamWithResultInitial
    extends StudentCompletedExamWithResultState {}

class StudentCompletedExamWithResultFetchInProgress
    extends StudentCompletedExamWithResultState {}

class StudentCompletedExamWithResultFetchSuccess
    extends StudentCompletedExamWithResultState {

  StudentCompletedExamWithResultFetchSuccess({
    required this.studentCompletedExamWithResultList,
  });
  final List<StudentResult> studentCompletedExamWithResultList;
}

class StudentCompletedExamWithResultFetchFailure
    extends StudentCompletedExamWithResultState {

  StudentCompletedExamWithResultFetchFailure(this.errorMessage);
  final String errorMessage;
}

class StudentCompletedExamWithResultCubit
    extends Cubit<StudentCompletedExamWithResultState> {

  StudentCompletedExamWithResultCubit(this._studentRepository)
      : super(StudentCompletedExamWithResultInitial());
  final StudentRepository _studentRepository;

  Future<void> fetchStudentCompletedExamWithResult({
    required int studentId,
  }) async {
    try {
      emit(StudentCompletedExamWithResultFetchInProgress());
      final result = await _studentRepository
          .fetchStudentCompletedExamListWithResult(studentId: studentId);

      emit(
        StudentCompletedExamWithResultFetchSuccess(
          studentCompletedExamWithResultList: result,
        ),
      );
    } catch (e) {
      emit(StudentCompletedExamWithResultFetchFailure(e.toString()));
    }
  }
}
