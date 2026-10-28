//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_operator_sign_in_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_operator_sign_in_data_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorOperatorSignInDataResponse {
  /// Returns a new [VendorOperatorSignInDataResponse] instance.
  VendorOperatorSignInDataResponse({
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
  final VendorOperatorSignInData data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final String? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorOperatorSignInDataResponse &&
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

  factory VendorOperatorSignInDataResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$VendorOperatorSignInDataResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$VendorOperatorSignInDataResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
