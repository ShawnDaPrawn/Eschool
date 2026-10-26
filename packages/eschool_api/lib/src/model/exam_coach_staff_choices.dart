//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_group_choice.dart';
import 'package:eschool_api/src/model/exam_coach_topic_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_staff_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachStaffChoices {
  /// Returns a new [ExamCoachStaffChoices] instance.
  ExamCoachStaffChoices({
    required this.groupId,

    required this.frameworks,

    required this.topics,

    required this.audienceGroups,

    required this.writable,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'frameworks', required: true, includeIfNull: false)
  final List<String> frameworks;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<ExamCoachTopicChoice> topics;

  @JsonKey(name: r'audience_groups', required: true, includeIfNull: false)
  final List<ExamCoachGroupChoice> audienceGroups;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachStaffChoices &&
          other.groupId == groupId &&
          other.frameworks == frameworks &&
          other.topics == topics &&
          other.audienceGroups == audienceGroups &&
          other.writable == writable;

  @override
  int get hashCode =>
      groupId.hashCode +
      frameworks.hashCode +
      topics.hashCode +
      audienceGroups.hashCode +
      writable.hashCode;

  factory ExamCoachStaffChoices.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachStaffChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachStaffChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
