// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
// ignore: depend_on_referenced_packages
import 'package:meta/meta.dart';

@immutable
abstract class SubjectMarksByStudentIdState {}

class SubjectMarksByStudentIdInitial extends SubjectMarksByStudentIdState {}

class SubjectMarksByStudentIdSubmitInProgress
    extends SubjectMarksByStudentIdState {}

class SubjectMarksByStudentIdSubmitSuccess
    extends SubjectMarksByStudentIdState {
  SubjectMarksByStudentIdSubmitSuccess({
    required this.isMarksUpdated,
    required this.successMessage,
  });
  final bool isMarksUpdated;
  final String successMessage;
}

class SubjectMarksByStudentIdSubmitFailure
    extends SubjectMarksByStudentIdState {
  SubjectMarksByStudentIdSubmitFailure({required this.exception});
  final ApiException exception;
}

class SubjectMarksByStudentIdCubit extends Cubit<SubjectMarksByStudentIdState> {
  SubjectMarksByStudentIdCubit({required this.studentRepository})
    : super(SubjectMarksByStudentIdInitial());
  StudentRepository studentRepository;
  //
  //This method is used to submit subject marks by student Id
  Future<void> submitSubjectMarksByStudentId({
    required int studentId,
    required int examId,
    required int classSectionId,
    required List<Map<String, dynamic>> bodyParameter,
  }) async {
    try {
      final parameter = {'marks_data': bodyParameter};
      emit(SubjectMarksByStudentIdSubmitInProgress());
      final Map<String, dynamic> result = await studentRepository
          .updateSubjectMarksByStudentId(
            studentId: studentId,
            examId: examId,
            bodyParameter: parameter,
            classSectionId: classSectionId,
          );

      emit(
        SubjectMarksByStudentIdSubmitSuccess(
          isMarksUpdated: !result['error'],
          successMessage: result['message'],
        ),
      );
    } catch (e) {
      emit(
        SubjectMarksByStudentIdSubmitFailure(
          exception: ApiException.fromException(e),
        ),
      );
    }
  }
}
