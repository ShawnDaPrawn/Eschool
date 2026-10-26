//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_licence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachLicence {
  /// Returns a new [ExamCoachLicence] instance.
  ExamCoachLicence({
    required this.kind,

    required this.reference,

    required this.statement,

    required this.confirmed,

    this.expiresOn,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachLicenceKindEnum.unknownDefaultOpenApi,
  )
  final ExamCoachLicenceKindEnum kind;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'statement', required: true, includeIfNull: false)
  final String statement;

  /// Must be true; a staff member attests concrete rights before publication.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @JsonKey(name: r'expires_on', required: false, includeIfNull: false)
  final String? expiresOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachLicence &&
          other.kind == kind &&
          other.reference == reference &&
          other.statement == statement &&
          other.confirmed == confirmed &&
          other.expiresOn == expiresOn;

  @override
  int get hashCode =>
      kind.hashCode +
      reference.hashCode +
      statement.hashCode +
      confirmed.hashCode +
      (expiresOn == null ? 0 : expiresOn.hashCode);

  factory ExamCoachLicence.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachLicenceFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachLicenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachLicenceKindEnum {
  @JsonValue(r'school_owned')
  schoolOwned(r'school_owned'),
  @JsonValue(r'open_licence')
  openLicence(r'open_licence'),
  @JsonValue(r'permission')
  permission(r'permission'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachLicenceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
