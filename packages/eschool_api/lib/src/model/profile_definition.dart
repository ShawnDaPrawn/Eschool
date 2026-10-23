//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_validation_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_definition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileDefinition {
  /// Returns a new [ProfileDefinition] instance.
  ProfileDefinition({
    required this.id,

    required this.categoryId,

    required this.key,

    required this.label,

    required this.type,

    required this.validation,

    required this.options,

    required this.visibleTo,

    required this.editableBy,

    required this.required_,

    required this.locked,

    required this.sensitive,

    required this.encrypted,

    required this.hidden,

    required this.sortOrder,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

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

  @JsonKey(name: r'visible_to', required: true, includeIfNull: false)
  final List<String> visibleTo;

  @JsonKey(name: r'editable_by', required: true, includeIfNull: false)
  final List<String> editableBy;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final bool locked;

  @JsonKey(name: r'sensitive', required: true, includeIfNull: false)
  final bool sensitive;

  @JsonKey(name: r'encrypted', required: true, includeIfNull: false)
  final bool encrypted;

  @JsonKey(name: r'hidden', required: true, includeIfNull: false)
  final bool hidden;

  @JsonKey(name: r'sort_order', required: true, includeIfNull: false)
  final int sortOrder;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileDefinition &&
          other.id == id &&
          other.categoryId == categoryId &&
          other.key == key &&
          other.label == label &&
          other.type == type &&
          other.validation == validation &&
          other.options == options &&
          other.visibleTo == visibleTo &&
          other.editableBy == editableBy &&
          other.required_ == required_ &&
          other.locked == locked &&
          other.sensitive == sensitive &&
          other.encrypted == encrypted &&
          other.hidden == hidden &&
          other.sortOrder == sortOrder;

  @override
  int get hashCode =>
      id.hashCode +
      categoryId.hashCode +
      key.hashCode +
      label.hashCode +
      type.hashCode +
      validation.hashCode +
      options.hashCode +
      visibleTo.hashCode +
      editableBy.hashCode +
      required_.hashCode +
      locked.hashCode +
      sensitive.hashCode +
      encrypted.hashCode +
      hidden.hashCode +
      sortOrder.hashCode;

  factory ProfileDefinition.fromJson(Map<String, dynamic> json) =>
      _$ProfileDefinitionFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileDefinitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
