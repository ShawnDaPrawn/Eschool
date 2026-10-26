//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_group_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachConfiguration {
  /// Returns a new [ExamCoachConfiguration] instance.
  ExamCoachConfiguration({
    required this.readerId,

    required this.learnerId,

    required this.staffGroups,

    required this.learnerGroups,

    required this.writable,

    this.photoFeedbackEnabled,
  });

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  @JsonKey(name: r'staff_groups', required: true, includeIfNull: false)
  final List<ExamCoachGroupChoice> staffGroups;

  @JsonKey(name: r'learner_groups', required: true, includeIfNull: false)
  final List<ExamCoachGroupChoice> learnerGroups;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  /// Optional current activation/readiness for automated photo feedback; every request still checks fresh source authority and budgets.
  @JsonKey(
    name: r'photo_feedback_enabled',
    required: false,
    includeIfNull: false,
  )
  final bool? photoFeedbackEnabled;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachConfiguration &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.staffGroups == staffGroups &&
          other.learnerGroups == learnerGroups &&
          other.writable == writable &&
          other.photoFeedbackEnabled == photoFeedbackEnabled;

  @override
  int get hashCode =>
      readerId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      staffGroups.hashCode +
      learnerGroups.hashCode +
      writable.hashCode +
      photoFeedbackEnabled.hashCode;

  factory ExamCoachConfiguration.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
