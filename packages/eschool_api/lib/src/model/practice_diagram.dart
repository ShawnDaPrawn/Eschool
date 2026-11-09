//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_diagram.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeDiagram {
  /// Returns a new [PracticeDiagram] instance.
  PracticeDiagram({
    required this.id,

    required this.width,

    required this.height,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'width', required: true, includeIfNull: false)
  final int width;

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'height', required: true, includeIfNull: false)
  final int height;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeDiagram &&
          other.id == id &&
          other.width == width &&
          other.height == height;

  @override
  int get hashCode => id.hashCode + width.hashCode + height.hashCode;

  factory PracticeDiagram.fromJson(Map<String, dynamic> json) =>
      _$PracticeDiagramFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeDiagramToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
