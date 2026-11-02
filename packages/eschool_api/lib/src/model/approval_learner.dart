//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_primary_identifier.dart';
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'approval_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ApprovalLearner {
  /// Returns a new [ApprovalLearner] instance.
  ApprovalLearner({
    required this.id,

    required this.name,

    required this.image,

    required this.studentNumber,

    this.primaryIdentifier,

    required this.school,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'image', required: true, includeIfNull: true)
  final String? image;

  @JsonKey(name: r'student_number', required: true, includeIfNull: true)
  final String? studentNumber;

  /// The learner's headline number (ADR 0007), the LURITS number or else the temporary NavigateEd number. Null when the caller may not see it (only the learner, a guardian who may approve, and staff with profile permission may) or when the learner has neither.
  @JsonKey(name: r'primary_identifier', required: false, includeIfNull: false)
  final LearnerPrimaryIdentifier? primaryIdentifier;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final SchoolSummary school;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ApprovalLearner &&
          other.id == id &&
          other.name == name &&
          other.image == image &&
          other.studentNumber == studentNumber &&
          other.primaryIdentifier == primaryIdentifier &&
          other.school == school;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (image == null ? 0 : image.hashCode) +
      (studentNumber == null ? 0 : studentNumber.hashCode) +
      (primaryIdentifier == null ? 0 : primaryIdentifier.hashCode) +
      school.hashCode;

  factory ApprovalLearner.fromJson(Map<String, dynamic> json) =>
      _$ApprovalLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$ApprovalLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
