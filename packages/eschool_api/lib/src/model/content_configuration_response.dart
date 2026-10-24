//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_configuration.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_configuration_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentConfigurationResponse {
  /// Returns a new [ContentConfigurationResponse] instance.
  ContentConfigurationResponse({
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
  final ContentConfiguration data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentConfigurationResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory ContentConfigurationResponse.fromJson(Map<String, dynamic> json) =>
      _$ContentConfigurationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ContentConfigurationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
