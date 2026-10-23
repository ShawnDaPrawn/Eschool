//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_value.dart';
import 'package:json_annotation/json_annotation.dart';

part 'update_my_profile_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateMyProfileRequest {
  /// Returns a new [UpdateMyProfileRequest] instance.
  UpdateMyProfileRequest({this.profileType, required this.values});

  @JsonKey(name: r'profile_type', required: false, includeIfNull: false)
  final String? profileType;

  @JsonKey(name: r'values', required: true, includeIfNull: false)
  final Map<String, ProfileValue> values;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateMyProfileRequest &&
          other.profileType == profileType &&
          other.values == values;

  @override
  int get hashCode => profileType.hashCode + values.hashCode;

  factory UpdateMyProfileRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateMyProfileRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdateMyProfileRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
