//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_elective_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayElectiveResult {
  /// Returns a new [PathwayElectiveResult] instance.
  PathwayElectiveResult({
    required this.title,

    required this.choose,

    required this.selected,

    required this.valid,
  });

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'choose', required: true, includeIfNull: false)
  final int choose;

  // minimum: 0
  // maximum: 30
  @JsonKey(name: r'selected', required: true, includeIfNull: false)
  final int selected;

  @JsonKey(name: r'valid', required: true, includeIfNull: false)
  final bool valid;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayElectiveResult &&
          other.title == title &&
          other.choose == choose &&
          other.selected == selected &&
          other.valid == valid;

  @override
  int get hashCode =>
      title.hashCode + choose.hashCode + selected.hashCode + valid.hashCode;

  factory PathwayElectiveResult.fromJson(Map<String, dynamic> json) =>
      _$PathwayElectiveResultFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayElectiveResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
