// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
// ignore: depend_on_referenced_packages
import 'package:meta/meta.dart';

@immutable
abstract class SubjectMarksBySubjectIdState {}

class SubjectMarksBySubjectIdInitial extends SubjectMarksBySubjectIdState {}

class SubjectMarksBySubjectIdSubmitInProgress
    extends SubjectMarksBySubjectIdState {}

class SubjectMarksBySubjectIdSubmitSuccess
    extends SubjectMarksBySubjectIdState {
  SubjectMarksBySubjectIdSubmitSuccess({
    required this.isMarksUpdated,
    required this.successMessage,
  });
  final bool isMarksUpdated;
  final String successMessage;
}

class SubjectMarksBySubjectIdSubmitFailure
    extends SubjectMarksBySubjectIdState {
  SubjectMarksBySubjectIdSubmitFailure({required this.exception});
  final ApiException exception;
}

class SubjectMarksBySubjectIdCubit extends Cubit<SubjectMarksBySubjectIdState> {
  SubjectMarksBySubjectIdCubit({required this.studentRepository})
    : super(SubjectMarksBySubjectIdInitial());
  StudentRepository studentRepository;
  //
  //This method is used to submit subject marks by student Id
  Future<void> submitSubjectMarksBySubjectId({
    required int subjectId,
    String? semesterId,
    required int examId,
    required int classSectionId,
    required List<Map<String, dynamic>> bodyParameter,
  }) async {
    try {
      final parameter = {'marks_data': bodyParameter};
      emit(SubjectMarksBySubjectIdSubmitInProgress());
      final Map<String, dynamic> result = await studentRepository
          .updateSubjectMarksBySubjectId(
            subjectId: subjectId,
            semesterId: semesterId,
            examId: examId,
            bodyParameter: parameter,
            classSectionId: classSectionId,
          );

      emit(
        SubjectMarksBySubjectIdSubmitSuccess(
          isMarksUpdated: !result['error'],
          successMessage: result['message'],
        ),
      );
    } catch (e) {
      emit(
        SubjectMarksBySubjectIdSubmitFailure(
          exception: ApiException.fromException(e),
        ),
      );
    }
  }
}
