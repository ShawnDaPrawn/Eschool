//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'text_messaging_response_data_notices_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TextMessagingResponseDataNoticesInner {
  /// Returns a new [TextMessagingResponseDataNoticesInner] instance.
  TextMessagingResponseDataNoticesInner({
    required this.type,

    required this.label,

    required this.offered,

    required this.sms,

    required this.whatsapp,

    required this.ownChoice,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  /// The channels the school switched on for you for this notice.
  @JsonKey(
    name: r'offered',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        TextMessagingResponseDataNoticesInnerOfferedEnum.unknownDefaultOpenApi,
  )
  final List<TextMessagingResponseDataNoticesInnerOfferedEnum> offered;

  @JsonKey(name: r'sms', required: true, includeIfNull: false)
  final bool sms;

  @JsonKey(name: r'whatsapp', required: true, includeIfNull: false)
  final bool whatsapp;

  /// Whether this is your own choice rather than the school's default.
  @JsonKey(name: r'own_choice', required: true, includeIfNull: false)
  final bool ownChoice;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TextMessagingResponseDataNoticesInner &&
          other.type == type &&
          other.label == label &&
          other.offered == offered &&
          other.sms == sms &&
          other.whatsapp == whatsapp &&
          other.ownChoice == ownChoice;

  @override
  int get hashCode =>
      type.hashCode +
      label.hashCode +
      offered.hashCode +
      sms.hashCode +
      whatsapp.hashCode +
      ownChoice.hashCode;

  factory TextMessagingResponseDataNoticesInner.fromJson(
    Map<String, dynamic> json,
  ) => _$TextMessagingResponseDataNoticesInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$TextMessagingResponseDataNoticesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TextMessagingResponseDataNoticesInnerOfferedEnum {
  @JsonValue(r'sms')
  sms(r'sms'),
  @JsonValue(r'whatsapp')
  whatsapp(r'whatsapp'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TextMessagingResponseDataNoticesInnerOfferedEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
