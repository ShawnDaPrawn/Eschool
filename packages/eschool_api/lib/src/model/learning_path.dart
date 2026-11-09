//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_topic.dart';
import 'package:eschool_api/src/model/learning_path_check.dart';
import 'package:eschool_api/src/model/learning_path_teach.dart';
import 'package:eschool_api/src/model/learning_path_item.dart';
import 'package:eschool_api/src/model/learning_path_upcoming_reviews_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPath {
  /// Returns a new [LearningPath] instance.
  LearningPath({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.groupId,

    required this.subjectId,

    required this.pathId,

    required this.revision,

    required this.step,

    required this.mode,

    required this.reason,

    required this.topic,

    required this.attempt,

    required this.maxAttempts,

    required this.notice,

    required this.message,

    required this.teach,

    required this.items,

    required this.check,

    required this.reviewDueOn,

    required this.upcomingReviews,

    required this.actions,

    required this.serverTime,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'path_id', required: true, includeIfNull: true)
  final int? pathId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'step',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathStepEnum.unknownDefaultOpenApi,
  )
  final LearningPathStepEnum step;

  @JsonKey(
    name: r'mode',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathModeEnum.unknownDefaultOpenApi,
  )
  final LearningPathModeEnum mode;

  @JsonKey(
    name: r'reason',
    required: true,
    includeIfNull: true,
    unknownEnumValue: LearningPathReasonEnum.unknownDefaultOpenApi,
  )
  final LearningPathReasonEnum? reason;

  @JsonKey(name: r'topic', required: true, includeIfNull: true)
  final LearningPathTopic? topic;

  // minimum: 0
  @JsonKey(name: r'attempt', required: true, includeIfNull: false)
  final int attempt;

  // minimum: 1
  @JsonKey(name: r'max_attempts', required: true, includeIfNull: false)
  final int maxAttempts;

  @JsonKey(
    name: r'notice',
    required: true,
    includeIfNull: true,
    unknownEnumValue: LearningPathNoticeEnum.unknownDefaultOpenApi,
  )
  final LearningPathNoticeEnum? notice;

  @JsonKey(name: r'message', required: true, includeIfNull: true)
  final String? message;

  @JsonKey(name: r'teach', required: true, includeIfNull: true)
  final LearningPathTeach? teach;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<LearningPathItem> items;

  @JsonKey(name: r'check', required: true, includeIfNull: true)
  final LearningPathCheck? check;

  @JsonKey(name: r'review_due_on', required: true, includeIfNull: true)
  final String? reviewDueOn;

  @JsonKey(name: r'upcoming_reviews', required: true, includeIfNull: false)
  final List<LearningPathUpcomingReviewsInner> upcomingReviews;

  @JsonKey(
    name: r'actions',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathActionsEnum.unknownDefaultOpenApi,
  )
  final List<LearningPathActionsEnum> actions;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPath &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.subjectId == subjectId &&
          other.pathId == pathId &&
          other.revision == revision &&
          other.step == step &&
          other.mode == mode &&
          other.reason == reason &&
          other.topic == topic &&
          other.attempt == attempt &&
          other.maxAttempts == maxAttempts &&
          other.notice == notice &&
          other.message == message &&
          other.teach == teach &&
          other.items == items &&
          other.check == check &&
          other.reviewDueOn == reviewDueOn &&
          other.upcomingReviews == upcomingReviews &&
          other.actions == actions &&
          other.serverTime == serverTime;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      groupId.hashCode +
      subjectId.hashCode +
      (pathId == null ? 0 : pathId.hashCode) +
      revision.hashCode +
      step.hashCode +
      mode.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      (topic == null ? 0 : topic.hashCode) +
      attempt.hashCode +
      maxAttempts.hashCode +
      (notice == null ? 0 : notice.hashCode) +
      (message == null ? 0 : message.hashCode) +
      (teach == null ? 0 : teach.hashCode) +
      items.hashCode +
      (check == null ? 0 : check.hashCode) +
      (reviewDueOn == null ? 0 : reviewDueOn.hashCode) +
      upcomingReviews.hashCode +
      actions.hashCode +
      serverTime.hashCode;

  factory LearningPath.fromJson(Map<String, dynamic> json) =>
      _$LearningPathFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathStepEnum {
  @JsonValue(r'gap')
  gap(r'gap'),
  @JsonValue(r'teach')
  teach(r'teach'),
  @JsonValue(r'practise')
  practise(r'practise'),
  @JsonValue(r'check')
  check(r'check'),
  @JsonValue(r'review')
  review(r'review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathStepEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearningPathModeEnum {
  @JsonValue(r'learn')
  learn(r'learn'),
  @JsonValue(r'review')
  review(r'review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathModeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearningPathReasonEnum {
  @JsonValue(r'not_yet_assessed')
  notYetAssessed(r'not_yet_assessed'),
  @JsonValue(r'prerequisite_gap')
  prerequisiteGap(r'prerequisite_gap'),
  @JsonValue(r'topic_gap')
  topicGap(r'topic_gap'),
  @JsonValue(r'review_due')
  reviewDue(r'review_due'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathReasonEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearningPathNoticeEnum {
  @JsonValue(r'passed')
  passed(r'passed'),
  @JsonValue(r'not_yet')
  notYet(r'not_yet'),
  @JsonValue(r'rest')
  rest(r'rest'),
  @JsonValue(r'caught_up')
  caughtUp(r'caught_up'),
  @JsonValue(r'nothing_ready')
  nothingReady(r'nothing_ready'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathNoticeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearningPathActionsEnum {
  @JsonValue(r'next')
  next(r'next'),
  @JsonValue(r'open_tutor')
  openTutor(r'open_tutor'),
  @JsonValue(r'practise')
  practise(r'practise'),
  @JsonValue(r'check')
  check(r'check'),
  @JsonValue(r'answer')
  answer(r'answer'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathActionsEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
