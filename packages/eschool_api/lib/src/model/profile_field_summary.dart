//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'profile_field_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileFieldSummary {
  /// Returns a new [ProfileFieldSummary] instance.
  ProfileFieldSummary({
    required this.profileType,

    required this.key,

    required this.label,

    required this.type,

    required this.required_,

    required this.locked,

    required this.categoryKey,

    required this.categoryLabel,
  });

  @JsonKey(name: r'profile_type', required: true, includeIfNull: false)
  final String profileType;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final bool locked;

  @JsonKey(name: r'category_key', required: true, includeIfNull: false)
  final String categoryKey;

  @JsonKey(name: r'category_label', required: true, includeIfNull: false)
  final String categoryLabel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileFieldSummary &&
          other.profileType == profileType &&
          other.key == key &&
          other.label == label &&
          other.type == type &&
          other.required_ == required_ &&
          other.locked == locked &&
          other.categoryKey == categoryKey &&
          other.categoryLabel == categoryLabel;

  @override
  int get hashCode =>
      profileType.hashCode +
      key.hashCode +
      label.hashCode +
      type.hashCode +
      required_.hashCode +
      locked.hashCode +
      categoryKey.hashCode +
      categoryLabel.hashCode;

  factory ProfileFieldSummary.fromJson(Map<String, dynamic> json) =>
      _$ProfileFieldSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFieldSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
