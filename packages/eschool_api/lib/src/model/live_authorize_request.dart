//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'live_authorize_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LiveAuthorizeRequest {
  /// Returns a new [LiveAuthorizeRequest] instance.
  LiveAuthorizeRequest({required this.socketId, required this.channelName});

  @JsonKey(name: r'socket_id', required: true, includeIfNull: false)
  final String socketId;

  @JsonKey(name: r'channel_name', required: true, includeIfNull: false)
  final String channelName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LiveAuthorizeRequest &&
          other.socketId == socketId &&
          other.channelName == channelName;

  @override
  int get hashCode => socketId.hashCode + channelName.hashCode;

  factory LiveAuthorizeRequest.fromJson(Map<String, dynamic> json) =>
      _$LiveAuthorizeRequestFromJson(json);

  Map<String, dynamic> toJson() => _$LiveAuthorizeRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
