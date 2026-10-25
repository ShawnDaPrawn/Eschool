//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_school.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorSchool {
  /// Returns a new [FacilitatorSchool] instance.
  FacilitatorSchool({required this.id, required this.name, required this.slug});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'slug', required: true, includeIfNull: false)
  final String slug;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorSchool &&
          other.id == id &&
          other.name == name &&
          other.slug == slug;

  @override
  int get hashCode => id.hashCode + name.hashCode + slug.hashCode;

  factory FacilitatorSchool.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorSchoolFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorSchoolToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
