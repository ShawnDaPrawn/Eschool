//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_diagram_reference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeDiagramReference {
  /// Returns a new [PracticeDiagramReference] instance.
  PracticeDiagramReference({
    this.source_,

    required this.id,

    this.width,

    this.height,
  });

  @JsonKey(
    name: r'source',
    required: false,
    includeIfNull: false,
    unknownEnumValue: PracticeDiagramReferenceSource_Enum.unknownDefaultOpenApi,
  )
  final PracticeDiagramReferenceSource_Enum? source_;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'width', required: false, includeIfNull: false)
  final int? width;

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'height', required: false, includeIfNull: false)
  final int? height;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeDiagramReference &&
          other.source_ == source_ &&
          other.id == id &&
          other.width == width &&
          other.height == height;

  @override
  int get hashCode =>
      source_.hashCode + id.hashCode + width.hashCode + height.hashCode;

  factory PracticeDiagramReference.fromJson(Map<String, dynamic> json) =>
      _$PracticeDiagramReferenceFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeDiagramReferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeDiagramReferenceSource_Enum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'library')
  library_(r'library'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeDiagramReferenceSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
