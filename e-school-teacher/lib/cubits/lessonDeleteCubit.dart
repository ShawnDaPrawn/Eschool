import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class LessonDeleteState {}

class LessonDeleteInitial extends LessonDeleteState {}

class LessonDeleteInProgress extends LessonDeleteState {}

class LessonDeleteSuccess extends LessonDeleteState {}

class LessonDeleteFailure extends LessonDeleteState {

  LessonDeleteFailure(this.errorMessage);
  final String errorMessage;
}

class LessonDeleteCubit extends Cubit<LessonDeleteState> {

  LessonDeleteCubit(this._lessonRepository) : super(LessonDeleteInitial());
  final LessonRepository _lessonRepository;

  Future<void> deleteLesson(int lessonId) async {
    emit(LessonDeleteInProgress());
    try {
      await _lessonRepository.deleteLesson(lessonId: lessonId);
      emit(LessonDeleteSuccess());
    } catch (e) {
      emit(LessonDeleteFailure(e.toString()));
    }
  }
}
