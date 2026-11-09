//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_explored_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_explored_subject.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsExploredSubject {
  /// Returns a new [StrengthsExploredSubject] instance.
  StrengthsExploredSubject({
    required this.subjectId,

    required this.subject,

    required this.topics,
  });

  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject', required: true, includeIfNull: false)
  final String subject;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<StrengthsExploredTopic> topics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsExploredSubject &&
          other.subjectId == subjectId &&
          other.subject == subject &&
          other.topics == topics;

  @override
  int get hashCode => subjectId.hashCode + subject.hashCode + topics.hashCode;

  factory StrengthsExploredSubject.fromJson(Map<String, dynamic> json) =>
      _$StrengthsExploredSubjectFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsExploredSubjectToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
