import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class StudentsByClassSectionState {}

class StudentsByClassSectionInitial extends StudentsByClassSectionState {}

class StudentsByClassSectionFetchInProgress
    extends StudentsByClassSectionState {}

class StudentsByClassSectionFetchSuccess extends StudentsByClassSectionState {
  StudentsByClassSectionFetchSuccess({required this.students});
  final List<Student> students;
}

class StudentsByClassSectionFetchFailure extends StudentsByClassSectionState {
  StudentsByClassSectionFetchFailure(this.errorMessage);
  final String errorMessage;
}

class StudentsByClassSectionCubit extends Cubit<StudentsByClassSectionState> {
  StudentsByClassSectionCubit(this._studentRepository)
    : super(StudentsByClassSectionInitial());
  final StudentRepository _studentRepository;

  void updateState(StudentsByClassSectionState updatedState) {
    emit(updatedState);
  }

  Future<void> fetchStudents({
    required int classSectionId,
    required int? subjectId,
    String? termId,
  }) async {
    emit(StudentsByClassSectionFetchInProgress());
    try {
      emit(
        StudentsByClassSectionFetchSuccess(
          students: await _studentRepository
              .getStudentsByClassSectionAndSubject(
                classSectionId: classSectionId,
                subjectId: subjectId,
                termId: termId,
              ),
        ),
      );
    } catch (e) {
      emit(StudentsByClassSectionFetchFailure(e.toString()));
    }
  }

  List<Student> getStudents() {
    return (state is StudentsByClassSectionFetchSuccess)
        ? (state as StudentsByClassSectionFetchSuccess).students
        : [];
  }
}
