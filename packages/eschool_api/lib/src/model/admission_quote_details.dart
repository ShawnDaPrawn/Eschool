//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_quote_details.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionQuoteDetails {
  /// Returns a new [AdmissionQuoteDetails] instance.
  AdmissionQuoteDetails({
    required this.typeId,

    required this.typeRevision,

    required this.eventId,

    required this.eventRevision,

    required this.eventName,

    required this.description,

    required this.kind,

    required this.capacity,

    required this.transferRule,

    required this.startsAt,

    required this.endsAt,

    required this.namedLearnerId,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'type_id', required: true, includeIfNull: false)
  final int typeId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'type_revision', required: true, includeIfNull: false)
  final int typeRevision;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'event_id', required: true, includeIfNull: false)
  final int eventId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'event_revision', required: true, includeIfNull: false)
  final int eventRevision;

  @JsonKey(name: r'event_name', required: true, includeIfNull: false)
  final String eventName;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionQuoteDetailsKindEnum.unknownDefaultOpenApi,
  )
  final AdmissionQuoteDetailsKindEnum kind;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'capacity', required: true, includeIfNull: false)
  final int capacity;

  @JsonKey(
    name: r'transfer_rule',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        AdmissionQuoteDetailsTransferRuleEnum.unknownDefaultOpenApi,
  )
  final AdmissionQuoteDetailsTransferRuleEnum transferRule;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'named_learner_id', required: true, includeIfNull: true)
  final int? namedLearnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionQuoteDetails &&
          other.typeId == typeId &&
          other.typeRevision == typeRevision &&
          other.eventId == eventId &&
          other.eventRevision == eventRevision &&
          other.eventName == eventName &&
          other.description == description &&
          other.kind == kind &&
          other.capacity == capacity &&
          other.transferRule == transferRule &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.namedLearnerId == namedLearnerId;

  @override
  int get hashCode =>
      typeId.hashCode +
      typeRevision.hashCode +
      eventId.hashCode +
      eventRevision.hashCode +
      eventName.hashCode +
      (description == null ? 0 : description.hashCode) +
      kind.hashCode +
      capacity.hashCode +
      transferRule.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      (namedLearnerId == null ? 0 : namedLearnerId.hashCode);

  factory AdmissionQuoteDetails.fromJson(Map<String, dynamic> json) =>
      _$AdmissionQuoteDetailsFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionQuoteDetailsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionQuoteDetailsKindEnum {
  @JsonValue(r'general')
  general(r'general'),
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionQuoteDetailsKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AdmissionQuoteDetailsTransferRuleEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'families')
  families(r'families'),
  @JsonValue(r'wallet_profiles')
  walletProfiles(r'wallet_profiles'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionQuoteDetailsTransferRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
