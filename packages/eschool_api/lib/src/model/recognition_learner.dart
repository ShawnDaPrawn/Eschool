//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionLearner {
  /// Returns a new [RecognitionLearner] instance.
  RecognitionLearner({
    required this.id,

    required this.name,

    required this.gradeLevelId,

    required this.sessionYearId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionLearner &&
          other.id == id &&
          other.name == name &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode;

  factory RecognitionLearner.fromJson(Map<String, dynamic> json) =>
      _$RecognitionLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
