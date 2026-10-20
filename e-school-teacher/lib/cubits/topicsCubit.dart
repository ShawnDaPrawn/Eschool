import 'package:eschool_teacher/data/models/topic.dart';
import 'package:eschool_teacher/data/repositories/topicRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TopicsState {}

class TopicsInitial extends TopicsState {}

class TopicsFetchInProgress extends TopicsState {}

class TopicsFetchSuccess extends TopicsState {

  TopicsFetchSuccess(this.topics);
  final List<Topic> topics;
}

class TopicsFetchFailure extends TopicsState {

  TopicsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class TopicsCubit extends Cubit<TopicsState> {

  TopicsCubit(this._topicRepository) : super(TopicsInitial());
  final TopicRepository _topicRepository;

  void updateState(TopicsState updatedState) {
    emit(updatedState);
  }

  Future<void> fetchTopics({required int lessonId}) async {
    emit(TopicsFetchInProgress());
    try {
      emit(
        TopicsFetchSuccess(
          await _topicRepository.fetchTopics(lessonId: lessonId),
        ),
      );
    } catch (e) {
      emit(TopicsFetchFailure(e.toString()));
    }
  }

  void deleteTopic(int topicId) {
    if (state is TopicsFetchSuccess) {
      final List<Topic> topics = (state as TopicsFetchSuccess).topics;
      topics.removeWhere((element) => element.id == topicId);
      emit(TopicsFetchSuccess(topics));
    }
  }
}
