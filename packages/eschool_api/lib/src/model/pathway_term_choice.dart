//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_term_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayTermChoice {
  /// Returns a new [PathwayTermChoice] instance.
  PathwayTermChoice({
    required this.id,

    required this.name,

    required this.sessionYearId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayTermChoice &&
          other.id == id &&
          other.name == name &&
          other.sessionYearId == sessionYearId;

  @override
  int get hashCode => id.hashCode + name.hashCode + sessionYearId.hashCode;

  factory PathwayTermChoice.fromJson(Map<String, dynamic> json) =>
      _$PathwayTermChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayTermChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
