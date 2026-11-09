//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_primary_identifier.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportLearner {
  /// Returns a new [LearnerPassportLearner] instance.
  LearnerPassportLearner({required this.name, required this.primaryIdentifier});

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  /// The learner's headline number (ADR 0007), the LURITS number or else the temporary NavigateEd number.
  @JsonKey(name: r'primary_identifier', required: true, includeIfNull: true)
  final LearnerPrimaryIdentifier? primaryIdentifier;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportLearner &&
          other.name == name &&
          other.primaryIdentifier == primaryIdentifier;

  @override
  int get hashCode =>
      name.hashCode +
      (primaryIdentifier == null ? 0 : primaryIdentifier.hashCode);

  factory LearnerPassportLearner.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
