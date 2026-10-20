import 'package:eschool_teacher/data/models/lesson.dart';
import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class LessonsState {}

class LessonsInitial extends LessonsState {}

class LessonsFetchInProgress extends LessonsState {}

class LessonsFetchSuccess extends LessonsState {

  LessonsFetchSuccess(this.lessons);
  final List<Lesson> lessons;
}

class LessonsFetchFailure extends LessonsState {

  LessonsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class LessonsCubit extends Cubit<LessonsState> {

  LessonsCubit(this._lessonRepository) : super(LessonsInitial());
  final LessonRepository _lessonRepository;

  Future<void> fetchLessons({
    required int classSectionId,
    required int subjectId,
    String? semesterId,
  }) async {
    emit(LessonsFetchInProgress());
    try {
      emit(
        LessonsFetchSuccess(
          await _lessonRepository.getLessons(
            classSectionId: classSectionId,
            subjectId: subjectId,
            semesterId: semesterId,
          ),
        ),
      );
    } catch (e) {
      emit(LessonsFetchFailure(e.toString()));
    }
  }

  void updateState(LessonsState updatedState) {
    emit(updatedState);
  }

  void deleteLesson(int lessonId) {
    if (state is LessonsFetchSuccess) {
      final List<Lesson> lessons = (state as LessonsFetchSuccess).lessons;
      lessons.removeWhere((element) => element.id == lessonId);
      emit(LessonsFetchSuccess(lessons));
    }
  }

  Lesson getLesson(int index) {
    if (state is LessonsFetchSuccess) {
      return (state as LessonsFetchSuccess).lessons[index];
    }
    return Lesson.fromJson({});
  }
}
