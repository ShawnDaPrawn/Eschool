//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_point_category.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerPointCategory {
  /// Returns a new [PeerPointCategory] instance.
  PeerPointCategory({
    required this.id,

    required this.name,

    required this.minimumPoints,

    required this.maximumPoints,

    required this.defaultPoints,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: -10000
  // maximum: 10000
  @JsonKey(name: r'minimum_points', required: true, includeIfNull: false)
  final int minimumPoints;

  // minimum: -10000
  // maximum: 10000
  @JsonKey(name: r'maximum_points', required: true, includeIfNull: false)
  final int maximumPoints;

  // minimum: -10000
  // maximum: 10000
  @JsonKey(name: r'default_points', required: true, includeIfNull: false)
  final int defaultPoints;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerPointCategory &&
          other.id == id &&
          other.name == name &&
          other.minimumPoints == minimumPoints &&
          other.maximumPoints == maximumPoints &&
          other.defaultPoints == defaultPoints;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      minimumPoints.hashCode +
      maximumPoints.hashCode +
      defaultPoints.hashCode;

  factory PeerPointCategory.fromJson(Map<String, dynamic> json) =>
      _$PeerPointCategoryFromJson(json);

  Map<String, dynamic> toJson() => _$PeerPointCategoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
