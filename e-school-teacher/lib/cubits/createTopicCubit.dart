import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/data/repositories/topicRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class CreateTopicState {}

class CreateTopicInitial extends CreateTopicState {}

class CreateTopicInProgress extends CreateTopicState {}

class CreateTopicSuccess extends CreateTopicState {}

class CreateTopicFailure extends CreateTopicState {
  CreateTopicFailure(this.exception);
  final ApiException exception;
}

class CreateTopicCubit extends Cubit<CreateTopicState> {
  CreateTopicCubit(this._topicRepository) : super(CreateTopicInitial());
  final TopicRepository _topicRepository;

  Future<void> createTopic({
    required String topicName,
    required int lessonId,
    required String topicDescription,
    required List<PickedStudyMaterial> files,
  }) async {
    emit(CreateTopicInProgress());
    try {
      final List<Map<String, dynamic>> filesJson = [];
      for (final file in files) {
        filesJson.add(await file.toJson());
      }
      await _topicRepository.createTopic(
        topicName: topicName,
        topicDescription: topicDescription,
        lessonId: lessonId,
        files: filesJson,
      );
      emit(CreateTopicSuccess());
    } catch (e) {
      emit(CreateTopicFailure(ApiException.fromException(e)));
    }
  }
}
