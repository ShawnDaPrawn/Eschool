//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/progress_band_settings.dart';
import 'package:json_annotation/json_annotation.dart';

part 'progress_band_settings_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressBandSettingsResponse {
  /// Returns a new [ProgressBandSettingsResponse] instance.
  ProgressBandSettingsResponse({
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
  final ProgressBandSettings data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressBandSettingsResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory ProgressBandSettingsResponse.fromJson(Map<String, dynamic> json) =>
      _$ProgressBandSettingsResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressBandSettingsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
