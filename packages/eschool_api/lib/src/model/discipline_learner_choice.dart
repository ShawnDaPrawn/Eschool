//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_learner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineLearnerChoice {
  /// Returns a new [DisciplineLearnerChoice] instance.
  DisciplineLearnerChoice({
    required this.learnerId,

    required this.name,

    required this.gradeLevelId,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineLearnerChoice &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.gradeLevelId == gradeLevelId;

  @override
  int get hashCode =>
      learnerId.hashCode + name.hashCode + gradeLevelId.hashCode;

  factory DisciplineLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$DisciplineLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
