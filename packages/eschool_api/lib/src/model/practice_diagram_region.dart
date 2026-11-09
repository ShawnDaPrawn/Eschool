//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_diagram_region.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeDiagramRegion {
  /// Returns a new [PracticeDiagramRegion] instance.
  PracticeDiagramRegion({required this.id, required this.x, required this.y});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  /// Per cent across from the left edge.
  // minimum: 0
  // maximum: 100
  @JsonKey(name: r'x', required: true, includeIfNull: false)
  final int x;

  /// Per cent down from the top edge.
  // minimum: 0
  // maximum: 100
  @JsonKey(name: r'y', required: true, includeIfNull: false)
  final int y;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeDiagramRegion &&
          other.id == id &&
          other.x == x &&
          other.y == y;

  @override
  int get hashCode => id.hashCode + x.hashCode + y.hashCode;

  factory PracticeDiagramRegion.fromJson(Map<String, dynamic> json) =>
      _$PracticeDiagramRegionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeDiagramRegionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
