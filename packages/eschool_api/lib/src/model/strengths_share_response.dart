//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_share.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_share_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsShareResponse {
  /// Returns a new [StrengthsShareResponse] instance.
  StrengthsShareResponse({
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
  final StrengthsShare data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsShareResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory StrengthsShareResponse.fromJson(Map<String, dynamic> json) =>
      _$StrengthsShareResponseFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsShareResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
