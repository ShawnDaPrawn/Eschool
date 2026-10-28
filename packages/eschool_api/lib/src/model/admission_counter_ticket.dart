//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_counter_ticket.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionCounterTicket {
  /// Returns a new [AdmissionCounterTicket] instance.
  AdmissionCounterTicket({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.id,

    required this.revision,

    required this.status,

    required this.eventName,

    required this.productName,

    required this.kind,

    required this.namedLearnerName,

    required this.startsAt,

    required this.endsAt,

    required this.canRedeem,

    required this.redeemedAt,

    required this.redeemedBy,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionCounterTicketStatusEnum.unknownDefaultOpenApi,
  )
  final AdmissionCounterTicketStatusEnum status;

  @JsonKey(name: r'event_name', required: true, includeIfNull: false)
  final String eventName;

  @JsonKey(name: r'product_name', required: true, includeIfNull: false)
  final String productName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionCounterTicketKindEnum.unknownDefaultOpenApi,
  )
  final AdmissionCounterTicketKindEnum kind;

  @JsonKey(name: r'named_learner_name', required: true, includeIfNull: true)
  final String? namedLearnerName;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'can_redeem', required: true, includeIfNull: false)
  final bool canRedeem;

  @JsonKey(name: r'redeemed_at', required: true, includeIfNull: true)
  final DateTime? redeemedAt;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'redeemed_by', required: true, includeIfNull: true)
  final int? redeemedBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionCounterTicket &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.id == id &&
          other.revision == revision &&
          other.status == status &&
          other.eventName == eventName &&
          other.productName == productName &&
          other.kind == kind &&
          other.namedLearnerName == namedLearnerName &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.canRedeem == canRedeem &&
          other.redeemedAt == redeemedAt &&
          other.redeemedBy == redeemedBy;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      id.hashCode +
      revision.hashCode +
      status.hashCode +
      eventName.hashCode +
      productName.hashCode +
      kind.hashCode +
      (namedLearnerName == null ? 0 : namedLearnerName.hashCode) +
      startsAt.hashCode +
      endsAt.hashCode +
      canRedeem.hashCode +
      (redeemedAt == null ? 0 : redeemedAt.hashCode) +
      (redeemedBy == null ? 0 : redeemedBy.hashCode);

  factory AdmissionCounterTicket.fromJson(Map<String, dynamic> json) =>
      _$AdmissionCounterTicketFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionCounterTicketToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionCounterTicketStatusEnum {
  @JsonValue(r'valid')
  valid(r'valid'),
  @JsonValue(r'redeemed')
  redeemed(r'redeemed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionCounterTicketStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AdmissionCounterTicketKindEnum {
  @JsonValue(r'general')
  general(r'general'),
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionCounterTicketKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
