//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_member_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolMemberSummary {
  /// Returns a new [PoolMemberSummary] instance.
  PoolMemberSummary({
    required this.id,

    required this.kind,

    required this.learnerId,

    required this.name,

    required this.occupation,

    required this.organisation,

    required this.offersCareerTalk,

    required this.offersJobShadow,

    required this.consentStatus,

    required this.consentedAt,

    required this.withdrawnAt,

    required this.vettingStatus,

    required this.vettedOn,

    required this.vettingExpiresOn,

    required this.bookableToday,

    required this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolMemberSummaryKindEnum.unknownDefaultOpenApi,
  )
  final PoolMemberSummaryKindEnum kind;

  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'occupation', required: true, includeIfNull: true)
  final String? occupation;

  @JsonKey(name: r'organisation', required: true, includeIfNull: true)
  final String? organisation;

  @JsonKey(name: r'offers_career_talk', required: true, includeIfNull: false)
  final bool offersCareerTalk;

  @JsonKey(name: r'offers_job_shadow', required: true, includeIfNull: false)
  final bool offersJobShadow;

  @JsonKey(
    name: r'consent_status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolMemberSummaryConsentStatusEnum.unknownDefaultOpenApi,
  )
  final PoolMemberSummaryConsentStatusEnum consentStatus;

  @JsonKey(name: r'consented_at', required: true, includeIfNull: true)
  final String? consentedAt;

  @JsonKey(name: r'withdrawn_at', required: true, includeIfNull: true)
  final String? withdrawnAt;

  @JsonKey(
    name: r'vetting_status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolMemberSummaryVettingStatusEnum.unknownDefaultOpenApi,
  )
  final PoolMemberSummaryVettingStatusEnum vettingStatus;

  @JsonKey(name: r'vetted_on', required: true, includeIfNull: true)
  final String? vettedOn;

  @JsonKey(name: r'vetting_expires_on', required: true, includeIfNull: true)
  final String? vettingExpiresOn;

  @JsonKey(name: r'bookable_today', required: true, includeIfNull: false)
  final bool bookableToday;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolMemberSummary &&
          other.id == id &&
          other.kind == kind &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.occupation == occupation &&
          other.organisation == organisation &&
          other.offersCareerTalk == offersCareerTalk &&
          other.offersJobShadow == offersJobShadow &&
          other.consentStatus == consentStatus &&
          other.consentedAt == consentedAt &&
          other.withdrawnAt == withdrawnAt &&
          other.vettingStatus == vettingStatus &&
          other.vettedOn == vettedOn &&
          other.vettingExpiresOn == vettingExpiresOn &&
          other.bookableToday == bookableToday &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      name.hashCode +
      (occupation == null ? 0 : occupation.hashCode) +
      (organisation == null ? 0 : organisation.hashCode) +
      offersCareerTalk.hashCode +
      offersJobShadow.hashCode +
      consentStatus.hashCode +
      (consentedAt == null ? 0 : consentedAt.hashCode) +
      (withdrawnAt == null ? 0 : withdrawnAt.hashCode) +
      vettingStatus.hashCode +
      (vettedOn == null ? 0 : vettedOn.hashCode) +
      (vettingExpiresOn == null ? 0 : vettingExpiresOn.hashCode) +
      bookableToday.hashCode +
      revision.hashCode;

  factory PoolMemberSummary.fromJson(Map<String, dynamic> json) =>
      _$PoolMemberSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$PoolMemberSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolMemberSummaryKindEnum {
  @JsonValue(r'alumnus')
  alumnus(r'alumnus'),
  @JsonValue(r'volunteer')
  volunteer(r'volunteer'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolMemberSummaryKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PoolMemberSummaryConsentStatusEnum {
  @JsonValue(r'invited')
  invited(r'invited'),
  @JsonValue(r'given')
  given(r'given'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolMemberSummaryConsentStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PoolMemberSummaryVettingStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'cleared')
  cleared(r'cleared'),
  @JsonValue(r'refused')
  refused(r'refused'),
  @JsonValue(r'revoked')
  revoked(r'revoked'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolMemberSummaryVettingStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
