import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EditLessonState {}

class EditLessonInitial extends EditLessonState {}

class EditLessonInProgress extends EditLessonState {}

class EditLessonSuccess extends EditLessonState {}

class EditLessonFailure extends EditLessonState {
  EditLessonFailure(this.exception);
  final ApiException exception;
}

class EditLessonCubit extends Cubit<EditLessonState> {
  EditLessonCubit(this._lessonRepository) : super(EditLessonInitial());
  final LessonRepository _lessonRepository;

  Future<void> editLesson({
    required String lessonName,
    required int lessonId,
    required int classSectionId,
    required int subjectId,
    String? semesterId,
    required String lessonDescription,
    required List<PickedStudyMaterial> files,
  }) async {
    emit(EditLessonInProgress());
    try {
      final List<Map<String, dynamic>> filesJosn = [];
      for (final file in files) {
        filesJosn.add(await file.toJson());
      }

      await _lessonRepository.updateLesson(
        lessonId: lessonId,
        lessonName: lessonName,
        classSectionId: classSectionId,
        subjectId: subjectId,
        semesterId: semesterId,
        lessonDescription: lessonDescription,
        files: filesJosn,
      );
      emit(EditLessonSuccess());
    } catch (e) {
      emit(EditLessonFailure(ApiException.fromException(e)));
    }
  }
}
