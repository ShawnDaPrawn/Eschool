//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'text_messaging_response_data_number.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TextMessagingResponseDataNumber {
  /// Returns a new [TextMessagingResponseDataNumber] instance.
  TextMessagingResponseDataNumber({
    required this.onRecord,

    required this.source_,

    required this.ending,

    required this.verified,

    required this.codeSent,

    required this.codeExpiresAt,

    required this.nextCodeAt,

    required this.lockedUntil,

    required this.canVerify,
  });

  /// Whether the school has a mobile number for you.
  @JsonKey(name: r'on_record', required: true, includeIfNull: false)
  final bool onRecord;

  /// parent is the number on your parent record at this school; account is the one on your own account (staff).
  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        TextMessagingResponseDataNumberSource_Enum.unknownDefaultOpenApi,
  )
  final TextMessagingResponseDataNumberSource_Enum? source_;

  /// The last three digits of that number.
  @JsonKey(name: r'ending', required: true, includeIfNull: true)
  final String? ending;

  @JsonKey(name: r'verified', required: true, includeIfNull: false)
  final bool verified;

  /// A code is waiting to be typed.
  @JsonKey(name: r'code_sent', required: true, includeIfNull: false)
  final bool codeSent;

  @JsonKey(name: r'code_expires_at', required: true, includeIfNull: true)
  final DateTime? codeExpiresAt;

  /// When another code can be asked for, or null when it can be now.
  @JsonKey(name: r'next_code_at', required: true, includeIfNull: true)
  final DateTime? nextCodeAt;

  @JsonKey(name: r'locked_until', required: true, includeIfNull: true)
  final DateTime? lockedUntil;

  /// Whether the school texts you anything, so the number can be verified.
  @JsonKey(name: r'can_verify', required: true, includeIfNull: false)
  final bool canVerify;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TextMessagingResponseDataNumber &&
          other.onRecord == onRecord &&
          other.source_ == source_ &&
          other.ending == ending &&
          other.verified == verified &&
          other.codeSent == codeSent &&
          other.codeExpiresAt == codeExpiresAt &&
          other.nextCodeAt == nextCodeAt &&
          other.lockedUntil == lockedUntil &&
          other.canVerify == canVerify;

  @override
  int get hashCode =>
      onRecord.hashCode +
      (source_ == null ? 0 : source_.hashCode) +
      (ending == null ? 0 : ending.hashCode) +
      verified.hashCode +
      codeSent.hashCode +
      (codeExpiresAt == null ? 0 : codeExpiresAt.hashCode) +
      (nextCodeAt == null ? 0 : nextCodeAt.hashCode) +
      (lockedUntil == null ? 0 : lockedUntil.hashCode) +
      canVerify.hashCode;

  factory TextMessagingResponseDataNumber.fromJson(Map<String, dynamic> json) =>
      _$TextMessagingResponseDataNumberFromJson(json);

  Map<String, dynamic> toJson() =>
      _$TextMessagingResponseDataNumberToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// parent is the number on your parent record at this school; account is the one on your own account (staff).
enum TextMessagingResponseDataNumberSource_Enum {
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'account')
  account(r'account'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TextMessagingResponseDataNumberSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
