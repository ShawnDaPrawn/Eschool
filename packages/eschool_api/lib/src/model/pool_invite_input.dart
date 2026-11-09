//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_invite_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolInviteInput {
  /// Returns a new [PoolInviteInput] instance.
  PoolInviteInput({
    required this.operationId,

    required this.kind,

    this.learnerId,

    required this.firstName,

    required this.lastName,

    required this.email,

    required this.initialPassword,

    this.occupation,

    this.organisation,

    required this.offersCareerTalk,

    required this.offersJobShadow,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolInviteInputKindEnum.unknownDefaultOpenApi,
  )
  final PoolInviteInputKindEnum kind;

  /// Required for an alumnus: the school's own adult leaver record.
  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'last_name', required: true, includeIfNull: false)
  final String lastName;

  /// A new address; existing accounts are not converted.
  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final String email;

  @JsonKey(name: r'initial_password', required: true, includeIfNull: false)
  final String initialPassword;

  @JsonKey(name: r'occupation', required: false, includeIfNull: false)
  final String? occupation;

  @JsonKey(name: r'organisation', required: false, includeIfNull: false)
  final String? organisation;

  @JsonKey(name: r'offers_career_talk', required: true, includeIfNull: false)
  final bool offersCareerTalk;

  @JsonKey(name: r'offers_job_shadow', required: true, includeIfNull: false)
  final bool offersJobShadow;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolInviteInput &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.learnerId == learnerId &&
          other.firstName == firstName &&
          other.lastName == lastName &&
          other.email == email &&
          other.initialPassword == initialPassword &&
          other.occupation == occupation &&
          other.organisation == organisation &&
          other.offersCareerTalk == offersCareerTalk &&
          other.offersJobShadow == offersJobShadow;

  @override
  int get hashCode =>
      operationId.hashCode +
      kind.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      firstName.hashCode +
      lastName.hashCode +
      email.hashCode +
      initialPassword.hashCode +
      (occupation == null ? 0 : occupation.hashCode) +
      (organisation == null ? 0 : organisation.hashCode) +
      offersCareerTalk.hashCode +
      offersJobShadow.hashCode;

  factory PoolInviteInput.fromJson(Map<String, dynamic> json) =>
      _$PoolInviteInputFromJson(json);

  Map<String, dynamic> toJson() => _$PoolInviteInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolInviteInputKindEnum {
  @JsonValue(r'alumnus')
  alumnus(r'alumnus'),
  @JsonValue(r'volunteer')
  volunteer(r'volunteer'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolInviteInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
