//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_sign_out_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorSignOutResponse {
  /// Returns a new [FacilitatorSignOutResponse] instance.
  FacilitatorSignOutResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: true)
  final Object? data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final String? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorSignOutResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      (data == null ? 0 : data.hashCode) +
      (code == null ? 0 : code.hashCode);

  factory FacilitatorSignOutResponse.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorSignOutResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorSignOutResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
