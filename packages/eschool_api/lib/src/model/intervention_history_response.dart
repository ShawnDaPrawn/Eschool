//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/intervention_history.dart';
import 'package:json_annotation/json_annotation.dart';

part 'intervention_history_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionHistoryResponse {
  /// Returns a new [InterventionHistoryResponse] instance.
  InterventionHistoryResponse({
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
  final InterventionHistory data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionHistoryResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory InterventionHistoryResponse.fromJson(Map<String, dynamic> json) =>
      _$InterventionHistoryResponseFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionHistoryResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
