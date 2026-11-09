//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_topic_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_quiz_topics.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeQuizTopics {
  /// Returns a new [PracticeQuizTopics] instance.
  PracticeQuizTopics({
    required this.learnerId,

    required this.groupId,

    required this.topics,

    required this.topicsMax,

    required this.questionsMin,

    required this.questionsMax,

    required this.writable,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<PracticeTestTopicOption> topics;

  // minimum: 1
  @JsonKey(name: r'topics_max', required: true, includeIfNull: false)
  final int topicsMax;

  // minimum: 1
  @JsonKey(name: r'questions_min', required: true, includeIfNull: false)
  final int questionsMin;

  // minimum: 1
  @JsonKey(name: r'questions_max', required: true, includeIfNull: false)
  final int questionsMax;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeQuizTopics &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.topics == topics &&
          other.topicsMax == topicsMax &&
          other.questionsMin == questionsMin &&
          other.questionsMax == questionsMax &&
          other.writable == writable;

  @override
  int get hashCode =>
      learnerId.hashCode +
      groupId.hashCode +
      topics.hashCode +
      topicsMax.hashCode +
      questionsMin.hashCode +
      questionsMax.hashCode +
      writable.hashCode;

  factory PracticeQuizTopics.fromJson(Map<String, dynamic> json) =>
      _$PracticeQuizTopicsFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeQuizTopicsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
