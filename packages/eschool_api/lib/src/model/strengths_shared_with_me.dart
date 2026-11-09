//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_shared_with_me.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSharedWithMe {
  /// Returns a new [StrengthsSharedWithMe] instance.
  StrengthsSharedWithMe({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.kind,

    required this.sharedOn,

    required this.expiresAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsSharedWithMeKindEnum.unknownDefaultOpenApi,
  )
  final StrengthsSharedWithMeKindEnum kind;

  @JsonKey(name: r'shared_on', required: true, includeIfNull: false)
  final DateTime sharedOn;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSharedWithMe &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.kind == kind &&
          other.sharedOn == sharedOn &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      kind.hashCode +
      sharedOn.hashCode +
      expiresAt.hashCode;

  factory StrengthsSharedWithMe.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSharedWithMeFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSharedWithMeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsSharedWithMeKindEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsSharedWithMeKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
