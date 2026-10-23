//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_value.dart';
import 'package:eschool_api/src/model/profile_validation_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_form_field.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileFormField {
  /// Returns a new [ProfileFormField] instance.
  ProfileFormField({
    required this.id,

    required this.key,

    required this.label,

    required this.type,

    required this.validation,

    required this.options,

    required this.required_,

    required this.locked,

    required this.editable,

    required this.sensitive,

    required this.value,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'validation', required: true, includeIfNull: false)
  final ProfileValidationRules validation;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<String> options;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final bool locked;

  @JsonKey(name: r'editable', required: true, includeIfNull: false)
  final bool editable;

  @JsonKey(name: r'sensitive', required: true, includeIfNull: false)
  final bool sensitive;

  @JsonKey(name: r'value', required: true, includeIfNull: true)
  final ProfileValue? value;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileFormField &&
          other.id == id &&
          other.key == key &&
          other.label == label &&
          other.type == type &&
          other.validation == validation &&
          other.options == options &&
          other.required_ == required_ &&
          other.locked == locked &&
          other.editable == editable &&
          other.sensitive == sensitive &&
          other.value == value;

  @override
  int get hashCode =>
      id.hashCode +
      key.hashCode +
      label.hashCode +
      type.hashCode +
      validation.hashCode +
      options.hashCode +
      required_.hashCode +
      locked.hashCode +
      editable.hashCode +
      sensitive.hashCode +
      (value == null ? 0 : value.hashCode);

  factory ProfileFormField.fromJson(Map<String, dynamic> json) =>
      _$ProfileFormFieldFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFormFieldToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
