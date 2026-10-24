//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceListResponse {
  /// Returns a new [CommunicationAudienceListResponse] instance.
  CommunicationAudienceListResponse({
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
  final CommunicationAudienceList data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceListResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory CommunicationAudienceListResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$CommunicationAudienceListResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CommunicationAudienceListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
