import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/lesson.dart';
import 'package:eschool/data/repositories/subjectRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SubjectLessonsState extends Equatable {}

class SubjectLessonsInitial extends SubjectLessonsState {
  @override
  List<Object?> get props => [];
}

class SubjectLessonsFetchInProgress extends SubjectLessonsState {
  @override
  List<Object?> get props => [];
}

class SubjectLessonsFetchSuccess extends SubjectLessonsState {

  SubjectLessonsFetchSuccess({required this.lessons});
  final List<Lesson> lessons;
  @override
  List<Object?> get props => [lessons];
}

class SubjectLessonsFetchFailure extends SubjectLessonsState {

  SubjectLessonsFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class SubjectLessonsCubit extends Cubit<SubjectLessonsState> {

  SubjectLessonsCubit(this._subjectRepository) : super(SubjectLessonsInitial());
  final SubjectRepository _subjectRepository;

  void fetchSubjectLessons({
    required int subjectId,
    required bool useParentApi,
    int? childId,
  }) {
    emit(SubjectLessonsFetchInProgress());
    _subjectRepository
        .getLessons(
          subjectId: subjectId,
          childId: childId ?? 0,
          useParentApi: useParentApi,
        )
        .then((lessons) => emit(SubjectLessonsFetchSuccess(lessons: lessons)))
        .catchError((e) => emit(SubjectLessonsFetchFailure(e.toString())));
  }
}
