import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/data/repositories/topicRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EditTopicState {}

class EditTopicInitial extends EditTopicState {}

class EditTopicInProgress extends EditTopicState {}

class EditTopicSuccess extends EditTopicState {}

class EditTopicFailure extends EditTopicState {
  EditTopicFailure(this.apiException);
  final ApiException apiException;
}

class EditTopicCubit extends Cubit<EditTopicState> {
  EditTopicCubit(this._topicRepository) : super(EditTopicInitial());
  final TopicRepository _topicRepository;

  Future<void> editTopic({
    required String topicName,
    required int lessonId,
    required String topicDescription,
    required int topicId,
    required List<PickedStudyMaterial> files,
  }) async {
    emit(EditTopicInProgress());
    try {
      final List<Map<String, dynamic>> filesJosn = [];
      for (final file in files) {
        filesJosn.add(await file.toJson());
      }
      await _topicRepository.editTopic(
        topicId: topicId,
        topicName: topicName,
        topicDescription: topicDescription,
        lessonId: lessonId,
        files: filesJosn,
      );

      emit(EditTopicSuccess());
    } catch (e) {
      emit(EditTopicFailure(ApiException.fromException(e)));
    }
  }
}
