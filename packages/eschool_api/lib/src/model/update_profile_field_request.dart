//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_validation_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'update_profile_field_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateProfileFieldRequest {
  /// Returns a new [UpdateProfileFieldRequest] instance.
  UpdateProfileFieldRequest({
    required this.categoryId,

    required this.key,

    required this.label,

    required this.type,

    this.validation,

    this.options,

    required this.visibleTo,

    required this.editableBy,

    this.required_,

    this.sensitive,

    this.encrypted,

    this.hidden,

    this.sortOrder,
  });

  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'validation', required: false, includeIfNull: false)
  final ProfileValidationRules? validation;

  @JsonKey(name: r'options', required: false, includeIfNull: false)
  final List<String>? options;

  @JsonKey(name: r'visible_to', required: true, includeIfNull: false)
  final List<String> visibleTo;

  @JsonKey(name: r'editable_by', required: true, includeIfNull: false)
  final List<String> editableBy;

  @JsonKey(name: r'required', required: false, includeIfNull: false)
  final bool? required_;

  @JsonKey(name: r'sensitive', required: false, includeIfNull: false)
  final bool? sensitive;

  @JsonKey(name: r'encrypted', required: false, includeIfNull: false)
  final bool? encrypted;

  @JsonKey(name: r'hidden', required: false, includeIfNull: false)
  final bool? hidden;

  @JsonKey(name: r'sort_order', required: false, includeIfNull: false)
  final int? sortOrder;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateProfileFieldRequest &&
          other.categoryId == categoryId &&
          other.key == key &&
          other.label == label &&
          other.type == type &&
          other.validation == validation &&
          other.options == options &&
          other.visibleTo == visibleTo &&
          other.editableBy == editableBy &&
          other.required_ == required_ &&
          other.sensitive == sensitive &&
          other.encrypted == encrypted &&
          other.hidden == hidden &&
          other.sortOrder == sortOrder;

  @override
  int get hashCode =>
      categoryId.hashCode +
      key.hashCode +
      label.hashCode +
      type.hashCode +
      validation.hashCode +
      options.hashCode +
      visibleTo.hashCode +
      editableBy.hashCode +
      required_.hashCode +
      sensitive.hashCode +
      encrypted.hashCode +
      hidden.hashCode +
      sortOrder.hashCode;

  factory UpdateProfileFieldRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateProfileFieldRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdateProfileFieldRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
