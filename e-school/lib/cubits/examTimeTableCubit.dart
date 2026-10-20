import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ExamTimeTableState {}

class ExamTimeTableInitial extends ExamTimeTableState {}

class ExamTimeTableFetchSuccess extends ExamTimeTableState {

  ExamTimeTableFetchSuccess({required this.examTimeTableList});
  final List<ExamTimeTable> examTimeTableList;
}

class ExamTimeTableFetchFailure extends ExamTimeTableState {

  ExamTimeTableFetchFailure(this.errorMessage);
  final String errorMessage;
}

class ExamTimeTableFetchInProgress extends ExamTimeTableState {}

class ExamTimeTableCubit extends Cubit<ExamTimeTableState> {

  ExamTimeTableCubit(this._studentRepository) : super(ExamTimeTableInitial());
  final StudentRepository _studentRepository;

  void fetchStudentExamsList({
    required bool useParentApi,
    required int examID, int? childId,
  }) {
    emit(ExamTimeTableFetchInProgress());
    _studentRepository
        .fetchExamTimeTable(
          examId: examID,
          childId: childId ?? 0,
          useParentApi: useParentApi,
        )
        .then(
          (value) => emit(ExamTimeTableFetchSuccess(examTimeTableList: value)),
        )
        .catchError((e) => emit(ExamTimeTableFetchFailure(e.toString())));
  }
}
