//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_session_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionPageResponse {
  /// Returns a new [PeerSessionPageResponse] instance.
  PeerSessionPageResponse({
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
  final PeerSessionPage data;

  // minimum: 200
  // maximum: 200
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory PeerSessionPageResponse.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionPageResponseFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
