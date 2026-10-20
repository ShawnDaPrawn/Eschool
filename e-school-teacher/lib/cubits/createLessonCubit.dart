import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class CreateLessonState {}

class CreateLessonInitial extends CreateLessonState {}

class CreateLessonInProgress extends CreateLessonState {}

class CreateLessonSuccess extends CreateLessonState {}

class CreateLessonFailure extends CreateLessonState {
  CreateLessonFailure(this.exception);
  final ApiException exception;
}

class CreateLessonCubit extends Cubit<CreateLessonState> {
  CreateLessonCubit(this._lessonRepository) : super(CreateLessonInitial());
  final LessonRepository _lessonRepository;

  Future<void> createLesson({
    required String lessonName,
    required int classSectionId,
    required int subjectId,
    String? semesterId,
    required String lessonDescription,
    required List<PickedStudyMaterial> files,
  }) async {
    emit(CreateLessonInProgress());
    try {
      final List<Map<String, dynamic>> filesJson = [];
      for (final file in files) {
        filesJson.add(await file.toJson());
      }

      await _lessonRepository.createLesson(
        lessonName: lessonName,
        classSectionId: classSectionId,
        subjectId: subjectId,
        semesterId: semesterId,
        lessonDescription: lessonDescription,
        files: filesJson,
      );
      emit(CreateLessonSuccess());
    } catch (e) {
      emit(CreateLessonFailure(ApiException.fromException(e)));
    }
  }
}
