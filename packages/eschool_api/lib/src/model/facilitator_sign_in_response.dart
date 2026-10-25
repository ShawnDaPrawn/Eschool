//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/facilitator_sign_in_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_sign_in_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorSignInResponse {
  /// Returns a new [FacilitatorSignInResponse] instance.
  FacilitatorSignInResponse({
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
  final FacilitatorSignInData data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final String? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorSignInResponse &&
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

  factory FacilitatorSignInResponse.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorSignInResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorSignInResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
