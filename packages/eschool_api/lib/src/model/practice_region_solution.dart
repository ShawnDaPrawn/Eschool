//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_region_solution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeRegionSolution {
  /// Returns a new [PracticeRegionSolution] instance.
  PracticeRegionSolution({required this.region, required this.label});

  @JsonKey(name: r'region', required: true, includeIfNull: false)
  final String region;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeRegionSolution &&
          other.region == region &&
          other.label == label;

  @override
  int get hashCode => region.hashCode + label.hashCode;

  factory PracticeRegionSolution.fromJson(Map<String, dynamic> json) =>
      _$PracticeRegionSolutionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeRegionSolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
