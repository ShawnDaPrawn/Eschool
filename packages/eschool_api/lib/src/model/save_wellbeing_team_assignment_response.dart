//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_team_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'save_wellbeing_team_assignment_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SaveWellbeingTeamAssignmentResponse {
  /// Returns a new [SaveWellbeingTeamAssignmentResponse] instance.
  SaveWellbeingTeamAssignmentResponse({
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
  final WellbeingTeamReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SaveWellbeingTeamAssignmentResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SaveWellbeingTeamAssignmentResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SaveWellbeingTeamAssignmentResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SaveWellbeingTeamAssignmentResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
