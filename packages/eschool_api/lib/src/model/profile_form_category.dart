//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_form_field.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_form_category.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileFormCategory {
  /// Returns a new [ProfileFormCategory] instance.
  ProfileFormCategory({
    required this.id,

    required this.key,

    required this.label,

    required this.fields,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'fields', required: true, includeIfNull: false)
  final List<ProfileFormField> fields;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileFormCategory &&
          other.id == id &&
          other.key == key &&
          other.label == label &&
          other.fields == fields;

  @override
  int get hashCode =>
      id.hashCode + key.hashCode + label.hashCode + fields.hashCode;

  factory ProfileFormCategory.fromJson(Map<String, dynamic> json) =>
      _$ProfileFormCategoryFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFormCategoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
