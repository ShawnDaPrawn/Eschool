//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_case_link_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'link_wellbeing_case_incident_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LinkWellbeingCaseIncidentResponse {
  /// Returns a new [LinkWellbeingCaseIncidentResponse] instance.
  LinkWellbeingCaseIncidentResponse({
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
  final WellbeingCaseLinkReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LinkWellbeingCaseIncidentResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory LinkWellbeingCaseIncidentResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$LinkWellbeingCaseIncidentResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LinkWellbeingCaseIncidentResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
