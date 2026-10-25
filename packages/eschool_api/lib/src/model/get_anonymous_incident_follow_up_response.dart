//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/incident_public_view.dart';
import 'package:json_annotation/json_annotation.dart';

part 'get_anonymous_incident_follow_up_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GetAnonymousIncidentFollowUpResponse {
  /// Returns a new [GetAnonymousIncidentFollowUpResponse] instance.
  GetAnonymousIncidentFollowUpResponse({
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
  final IncidentPublicView data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GetAnonymousIncidentFollowUpResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GetAnonymousIncidentFollowUpResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$GetAnonymousIncidentFollowUpResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$GetAnonymousIncidentFollowUpResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
