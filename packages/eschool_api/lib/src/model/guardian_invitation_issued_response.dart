//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/guardian_invitation_issued.dart';
import 'package:json_annotation/json_annotation.dart';

part 'guardian_invitation_issued_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianInvitationIssuedResponse {
  /// Returns a new [GuardianInvitationIssuedResponse] instance.
  GuardianInvitationIssuedResponse({
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
  final GuardianInvitationIssued data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianInvitationIssuedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GuardianInvitationIssuedResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$GuardianInvitationIssuedResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$GuardianInvitationIssuedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
