//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'update_my_text_messaging_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateMyTextMessagingRequest {
  /// Returns a new [UpdateMyTextMessagingRequest] instance.
  UpdateMyTextMessagingRequest({
    required this.type,

    required this.sms,

    required this.whatsapp,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'sms', required: true, includeIfNull: false)
  final bool sms;

  @JsonKey(name: r'whatsapp', required: true, includeIfNull: false)
  final bool whatsapp;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateMyTextMessagingRequest &&
          other.type == type &&
          other.sms == sms &&
          other.whatsapp == whatsapp;

  @override
  int get hashCode => type.hashCode + sms.hashCode + whatsapp.hashCode;

  factory UpdateMyTextMessagingRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateMyTextMessagingRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdateMyTextMessagingRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
