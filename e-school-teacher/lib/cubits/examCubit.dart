// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';

abstract class ExamDetailsState {}

class ExamDetailsInitial extends ExamDetailsState {}

class ExamDetailsFetchSuccess extends ExamDetailsState {

  ExamDetailsFetchSuccess({required this.examList});
  final List<Exam> examList;
}

class ExamDetailsFetchFailure extends ExamDetailsState {

  ExamDetailsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class ExamDetailsFetchInProgress extends ExamDetailsState {}

class ExamDetailsCubit extends Cubit<ExamDetailsState> {

  ExamDetailsCubit(this._studentRepository) : super(ExamDetailsInitial());
  final StudentRepository _studentRepository;

  void fetchStudentExamsList({
    required int examStatus,
    int? classSectionId,
    int? studentId,
    int? publishStatus,
  }) {
    emit(ExamDetailsFetchInProgress());
    _studentRepository
        .fetchExamsList(
          examStatus: examStatus,
          studentID: studentId,
          publishStatus: publishStatus,
          classSectionId: classSectionId,
        )
        .then((value) => emit(ExamDetailsFetchSuccess(examList: value)))
        .catchError((e) => emit(ExamDetailsFetchFailure(e.toString())));
  }

  List<Exam> getAllExams() {
    if (state is ExamDetailsFetchSuccess) {
      return (state as ExamDetailsFetchSuccess).examList;
    }
    return [];
  }

  List<String> getExamName() {
    return getAllExams().map((exams) => exams.getExamName()).toList();
  }

  Exam getExamDetails({required int index}) {
    return getAllExams()[index];
  }
}
