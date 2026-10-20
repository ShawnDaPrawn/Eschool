import 'package:eschool_teacher/data/models/topic.dart';
import 'package:eschool_teacher/utils/api.dart';

class TopicRepository {
  Future<List<Topic>> fetchTopics({required int lessonId}) async {
    try {
      final result = await Api.get(
        url: Api.getTopics,
        useAuthToken: true,
        queryParameters: {'lesson_id': lessonId},
      );

      return (result['data'] as List)
          .map((topic) => Topic.fromJson(Map.from(topic)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> deleteTopic({required int topicId}) async {
    try {
      await Api.post(
        url: Api.deleteTopic,
        useAuthToken: true,
        body: {'topic_id': topicId},
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> createTopic({
    required String topicName,
    required String topicDescription,
    required int lessonId,
    required List<Map<String, dynamic>> files,
  }) async {
    final Map<String, dynamic> body = {
      'name': topicName,
      'description': topicDescription,
      'lesson_id': lessonId,
    };

    if (files.isNotEmpty) {
      body['file'] = files;
    }

    await Api.post(url: Api.createTopic, useAuthToken: true, body: body);
  }

  Future<void> editTopic({
    required String topicName,
    required String topicDescription,
    required int lessonId,
    required int topicId,
    required List<Map<String, dynamic>> files,
  }) async {
    final Map<String, dynamic> body = {
      'name': topicName,
      'topic_id': topicId,
      'description': topicDescription,
      'lesson_id': lessonId,
    };

    if (files.isNotEmpty) {
      body['file'] = files;
    }

    await Api.post(url: Api.updateTopic, useAuthToken: true, body: body);
  }
}
