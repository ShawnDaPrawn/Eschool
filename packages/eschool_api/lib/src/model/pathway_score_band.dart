//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_score_band.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayScoreBand {
  /// Returns a new [PathwayScoreBand] instance.
  PathwayScoreBand({required this.minimumBp, required this.points});

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'minimum_bp', required: true, includeIfNull: false)
  final int minimumBp;

  // minimum: 0
  // maximum: 20
  @JsonKey(name: r'points', required: true, includeIfNull: false)
  final int points;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayScoreBand &&
          other.minimumBp == minimumBp &&
          other.points == points;

  @override
  int get hashCode => minimumBp.hashCode + points.hashCode;

  factory PathwayScoreBand.fromJson(Map<String, dynamic> json) =>
      _$PathwayScoreBandFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayScoreBandToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
