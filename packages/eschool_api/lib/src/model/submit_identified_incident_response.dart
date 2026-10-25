//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/incident_submission_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'submit_identified_incident_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubmitIdentifiedIncidentResponse {
  /// Returns a new [SubmitIdentifiedIncidentResponse] instance.
  SubmitIdentifiedIncidentResponse({
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
  final IncidentSubmissionReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubmitIdentifiedIncidentResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SubmitIdentifiedIncidentResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SubmitIdentifiedIncidentResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SubmitIdentifiedIncidentResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
