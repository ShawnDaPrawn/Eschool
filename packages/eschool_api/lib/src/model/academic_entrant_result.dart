//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_entrant_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicEntrantResult {
  /// Returns a new [AcademicEntrantResult] instance.
  AcademicEntrantResult({
    required this.learnerId,

    required this.score,

    required this.place,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 0
  // maximum: 9999
  @JsonKey(name: r'score', required: true, includeIfNull: true)
  final int? score;

  // minimum: 1
  // maximum: 999
  @JsonKey(name: r'place', required: true, includeIfNull: true)
  final int? place;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicEntrantResult &&
          other.learnerId == learnerId &&
          other.score == score &&
          other.place == place;

  @override
  int get hashCode =>
      learnerId.hashCode +
      (score == null ? 0 : score.hashCode) +
      (place == null ? 0 : place.hashCode);

  factory AcademicEntrantResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicEntrantResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicEntrantResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
