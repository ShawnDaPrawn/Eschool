//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_profile_school.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_profile.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolProfile {
  /// Returns a new [PoolProfile] instance.
  PoolProfile({
    required this.userId,

    required this.name,

    required this.school,

    required this.poolMemberId,

    required this.kind,

    required this.offersCareerTalk,

    required this.offersJobShadow,

    required this.consentedAt,

    required this.vettingStatus,

    required this.vettingExpiresOn,

    required this.vetted,
  });

  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final PoolProfileSchool school;

  @JsonKey(name: r'pool_member_id', required: true, includeIfNull: false)
  final int poolMemberId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolProfileKindEnum.unknownDefaultOpenApi,
  )
  final PoolProfileKindEnum kind;

  @JsonKey(name: r'offers_career_talk', required: true, includeIfNull: false)
  final bool offersCareerTalk;

  @JsonKey(name: r'offers_job_shadow', required: true, includeIfNull: false)
  final bool offersJobShadow;

  @JsonKey(name: r'consented_at', required: true, includeIfNull: true)
  final String? consentedAt;

  @JsonKey(name: r'vetting_status', required: true, includeIfNull: false)
  final String vettingStatus;

  @JsonKey(name: r'vetting_expires_on', required: true, includeIfNull: true)
  final String? vettingExpiresOn;

  @JsonKey(name: r'vetted', required: true, includeIfNull: false)
  final bool vetted;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolProfile &&
          other.userId == userId &&
          other.name == name &&
          other.school == school &&
          other.poolMemberId == poolMemberId &&
          other.kind == kind &&
          other.offersCareerTalk == offersCareerTalk &&
          other.offersJobShadow == offersJobShadow &&
          other.consentedAt == consentedAt &&
          other.vettingStatus == vettingStatus &&
          other.vettingExpiresOn == vettingExpiresOn &&
          other.vetted == vetted;

  @override
  int get hashCode =>
      userId.hashCode +
      name.hashCode +
      school.hashCode +
      poolMemberId.hashCode +
      kind.hashCode +
      offersCareerTalk.hashCode +
      offersJobShadow.hashCode +
      (consentedAt == null ? 0 : consentedAt.hashCode) +
      vettingStatus.hashCode +
      (vettingExpiresOn == null ? 0 : vettingExpiresOn.hashCode) +
      vetted.hashCode;

  factory PoolProfile.fromJson(Map<String, dynamic> json) =>
      _$PoolProfileFromJson(json);

  Map<String, dynamic> toJson() => _$PoolProfileToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolProfileKindEnum {
  @JsonValue(r'alumnus')
  alumnus(r'alumnus'),
  @JsonValue(r'volunteer')
  volunteer(r'volunteer'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolProfileKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
