//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_definition.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_category_definition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileCategoryDefinition {
  /// Returns a new [ProfileCategoryDefinition] instance.
  ProfileCategoryDefinition({
    required this.id,

    required this.profileType,

    required this.key,

    required this.label,

    required this.sortOrder,

    required this.fields,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'profile_type', required: true, includeIfNull: false)
  final String profileType;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'sort_order', required: true, includeIfNull: false)
  final int sortOrder;

  @JsonKey(name: r'fields', required: true, includeIfNull: false)
  final List<ProfileDefinition> fields;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileCategoryDefinition &&
          other.id == id &&
          other.profileType == profileType &&
          other.key == key &&
          other.label == label &&
          other.sortOrder == sortOrder &&
          other.fields == fields;

  @override
  int get hashCode =>
      id.hashCode +
      profileType.hashCode +
      key.hashCode +
      label.hashCode +
      sortOrder.hashCode +
      fields.hashCode;

  factory ProfileCategoryDefinition.fromJson(Map<String, dynamic> json) =>
      _$ProfileCategoryDefinitionFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileCategoryDefinitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
