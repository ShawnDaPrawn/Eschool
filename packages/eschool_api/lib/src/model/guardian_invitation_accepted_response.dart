//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'guardian_invitation_accepted_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianInvitationAcceptedResponse {
  /// Returns a new [GuardianInvitationAcceptedResponse] instance.
  GuardianInvitationAcceptedResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,

    required this.token,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final Map<String, Object> data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  /// Native bearer token; empty for a cookie-authenticated portal.
  @JsonKey(name: r'token', required: true, includeIfNull: false)
  final String token;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianInvitationAcceptedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code &&
          other.token == token;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      data.hashCode +
      code.hashCode +
      token.hashCode;

  factory GuardianInvitationAcceptedResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$GuardianInvitationAcceptedResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$GuardianInvitationAcceptedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
