// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/repositories/studentRepository.dart';

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
    required bool useParentApi,
    required int examStatus,
    int? childId,
    String? termId,
  }) {
    emit(ExamDetailsFetchInProgress());
    _studentRepository
        .fetchExamsList(
          childId: childId ?? 0,
          examStatus: examStatus,
          useParentApi: useParentApi,
          termId: termId,
        )
        .then((value) => emit(ExamDetailsFetchSuccess(examList: value)))
        .catchError((e) => emit(ExamDetailsFetchFailure(e.toString())));
  }
}
