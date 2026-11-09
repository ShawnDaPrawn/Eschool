//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_recipient.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsRecipient {
  /// Returns a new [StrengthsRecipient] instance.
  StrengthsRecipient({
    required this.userId,

    required this.name,

    required this.kind,

    required this.detail,
  });

  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsRecipientKindEnum.unknownDefaultOpenApi,
  )
  final StrengthsRecipientKindEnum kind;

  /// The guardian's relationship, or the subjects the teacher teaches the learner.
  @JsonKey(name: r'detail', required: true, includeIfNull: false)
  final String detail;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsRecipient &&
          other.userId == userId &&
          other.name == name &&
          other.kind == kind &&
          other.detail == detail;

  @override
  int get hashCode =>
      userId.hashCode + name.hashCode + kind.hashCode + detail.hashCode;

  factory StrengthsRecipient.fromJson(Map<String, dynamic> json) =>
      _$StrengthsRecipientFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsRecipientToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsRecipientKindEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsRecipientKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
