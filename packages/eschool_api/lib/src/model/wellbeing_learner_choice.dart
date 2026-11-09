//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_primary_identifier.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_learner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingLearnerChoice {
  /// Returns a new [WellbeingLearnerChoice] instance.
  WellbeingLearnerChoice({
    required this.id,

    required this.name,

    required this.admissionNo,

    this.primaryIdentifier,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'admission_no', required: true, includeIfNull: false)
  final String admissionNo;

  /// The learner's headline number (ADR 0007), the LURITS number or else the temporary NavigateEd number. Null when the caller may not see it (only the learner, a guardian who may approve, and staff with profile permission may) or when the learner has neither.
  @JsonKey(name: r'primary_identifier', required: false, includeIfNull: false)
  final LearnerPrimaryIdentifier? primaryIdentifier;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingLearnerChoice &&
          other.id == id &&
          other.name == name &&
          other.admissionNo == admissionNo &&
          other.primaryIdentifier == primaryIdentifier;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      admissionNo.hashCode +
      (primaryIdentifier == null ? 0 : primaryIdentifier.hashCode);

  factory WellbeingLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$WellbeingLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
