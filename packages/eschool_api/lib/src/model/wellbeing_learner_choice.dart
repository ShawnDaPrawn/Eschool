//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
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
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'admission_no', required: true, includeIfNull: false)
  final String admissionNo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingLearnerChoice &&
          other.id == id &&
          other.name == name &&
          other.admissionNo == admissionNo;

  @override
  int get hashCode => id.hashCode + name.hashCode + admissionNo.hashCode;

  factory WellbeingLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$WellbeingLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
