//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_value.dart';
import 'package:json_annotation/json_annotation.dart';

part 'update_person_profile_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdatePersonProfileRequest {
  /// Returns a new [UpdatePersonProfileRequest] instance.
  UpdatePersonProfileRequest({this.profileType, required this.values});

  @JsonKey(name: r'profile_type', required: false, includeIfNull: false)
  final String? profileType;

  @JsonKey(name: r'values', required: true, includeIfNull: false)
  final Map<String, ProfileValue> values;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdatePersonProfileRequest &&
          other.profileType == profileType &&
          other.values == values;

  @override
  int get hashCode => profileType.hashCode + values.hashCode;

  factory UpdatePersonProfileRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdatePersonProfileRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdatePersonProfileRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
