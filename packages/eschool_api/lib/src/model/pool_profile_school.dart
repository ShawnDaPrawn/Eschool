//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_profile_school.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolProfileSchool {
  /// Returns a new [PoolProfileSchool] instance.
  PoolProfileSchool({required this.id, required this.name, required this.slug});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'slug', required: true, includeIfNull: false)
  final String slug;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolProfileSchool &&
          other.id == id &&
          other.name == name &&
          other.slug == slug;

  @override
  int get hashCode => id.hashCode + name.hashCode + slug.hashCode;

  factory PoolProfileSchool.fromJson(Map<String, dynamic> json) =>
      _$PoolProfileSchoolFromJson(json);

  Map<String, dynamic> toJson() => _$PoolProfileSchoolToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
