//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_configuration.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_configuration_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskConfigurationResponse {
  /// Returns a new [RiskConfigurationResponse] instance.
  RiskConfigurationResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final RiskConfiguration data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskConfigurationResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory RiskConfigurationResponse.fromJson(Map<String, dynamic> json) =>
      _$RiskConfigurationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$RiskConfigurationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
