//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_operator_profile.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_operator_profile_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorOperatorProfileResponse {
  /// Returns a new [VendorOperatorProfileResponse] instance.
  VendorOperatorProfileResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final VendorOperatorProfile data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final String? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorOperatorProfileResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      data.hashCode +
      (code == null ? 0 : code.hashCode);

  factory VendorOperatorProfileResponse.fromJson(Map<String, dynamic> json) =>
      _$VendorOperatorProfileResponseFromJson(json);

  Map<String, dynamic> toJson() => _$VendorOperatorProfileResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
