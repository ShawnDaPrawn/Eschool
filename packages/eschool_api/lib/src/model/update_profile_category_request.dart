//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'update_profile_category_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateProfileCategoryRequest {
  /// Returns a new [UpdateProfileCategoryRequest] instance.
  UpdateProfileCategoryRequest({
    required this.profileType,

    required this.key,

    required this.label,

    this.sortOrder,
  });

  @JsonKey(name: r'profile_type', required: true, includeIfNull: false)
  final String profileType;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  // minimum: 0
  // maximum: 100000
  @JsonKey(name: r'sort_order', required: false, includeIfNull: false)
  final int? sortOrder;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateProfileCategoryRequest &&
          other.profileType == profileType &&
          other.key == key &&
          other.label == label &&
          other.sortOrder == sortOrder;

  @override
  int get hashCode =>
      profileType.hashCode + key.hashCode + label.hashCode + sortOrder.hashCode;

  factory UpdateProfileCategoryRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateProfileCategoryRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdateProfileCategoryRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
