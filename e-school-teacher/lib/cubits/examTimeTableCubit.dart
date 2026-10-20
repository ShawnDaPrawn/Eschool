import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ExamTimeTableState {}

class ExamTimeTableInitial extends ExamTimeTableState {}

class ExamTimeTableFetchSuccess extends ExamTimeTableState {
  ExamTimeTableFetchSuccess({required this.examTimeTableList});
  final List<ExamTimeTable> examTimeTableList;
}

class ExamTimeTableFetchFailure extends ExamTimeTableState {
  ExamTimeTableFetchFailure(this.exception);
  final ApiException exception;
}

class ExamTimeTableFetchInProgress extends ExamTimeTableState {}

class ExamTimeTableCubit extends Cubit<ExamTimeTableState> {
  ExamTimeTableCubit(this._studentRepository) : super(ExamTimeTableInitial());
  final StudentRepository _studentRepository;

  void fetchStudentExamTimeTable({required int examID, required int classId}) {
    emit(ExamTimeTableFetchInProgress());
    _studentRepository
        .fetchExamTimeTable(examId: examID, classId: classId)
        .then(
          (value) => emit(ExamTimeTableFetchSuccess(examTimeTableList: value)),
        )
        .catchError(
          (e) => emit(ExamTimeTableFetchFailure(ApiException.fromException(e))),
        );
  }

  void updateState(ExamTimeTableState updateState) {
    emit(updateState);
  }

  List<ExamTimeTable> getAllSubjectOfExamTimeTable() {
    if (state is ExamTimeTableFetchSuccess) {
      return (state as ExamTimeTableFetchSuccess).examTimeTableList;
    }
    return [];
  }

  List<Subject> getAllSubjects() {
    final list = List<Subject>.from(
      getAllSubjectOfExamTimeTable().map((e) => e.subject),
    );

    return list;
  }

  String getTotalMarksOfSubject({required int subjectId}) {
    String totalMarks = '';
    getAllSubjectOfExamTimeTable().forEach((element) {
      if (element.subject!.id == subjectId) {
        totalMarks = element.totalMarks.toString();
      }
    });

    return totalMarks;
  }

  List<String> getSubjectName() {
    return getAllSubjects()
        .map<String>((subject) => subject.subjectNameWithTypeAndSemester)
        .toList();
  }

  Subject getSubjectDetails({required int index}) {
    return getAllSubjects()[index];
  }
}
