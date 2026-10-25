//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_configuration.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_configuration_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverConfigurationResponse {
  /// Returns a new [CoverConfigurationResponse] instance.
  CoverConfigurationResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final CoverConfiguration data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverConfigurationResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory CoverConfigurationResponse.fromJson(Map<String, dynamic> json) =>
      _$CoverConfigurationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CoverConfigurationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
