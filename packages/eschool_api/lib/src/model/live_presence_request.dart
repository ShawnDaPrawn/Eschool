//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'live_presence_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LivePresenceRequest {
  /// Returns a new [LivePresenceRequest] instance.
  LivePresenceRequest({required this.leaseId});

  @JsonKey(name: r'lease_id', required: true, includeIfNull: false)
  final String leaseId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LivePresenceRequest && other.leaseId == leaseId;

  @override
  int get hashCode => leaseId.hashCode;

  factory LivePresenceRequest.fromJson(Map<String, dynamic> json) =>
      _$LivePresenceRequestFromJson(json);

  Map<String, dynamic> toJson() => _$LivePresenceRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
