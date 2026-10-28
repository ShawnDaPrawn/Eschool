//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_event.dart';
import 'package:json_annotation/json_annotation.dart';

part 'admission_ticket.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTicket {
  /// Returns a new [AdmissionTicket] instance.
  AdmissionTicket({
    required this.schoolId,

    required this.actorUserId,

    required this.id,

    required this.holderUserId,

    required this.revision,

    required this.status,

    required this.canTransfer,

    required this.event,

    required this.typeId,

    required this.kind,

    required this.transferRule,

    required this.productName,

    required this.currency,

    required this.unitPriceCents,

    required this.orderNumber,

    required this.namedLearnerId,

    required this.namedLearnerName,

    required this.qr,

    required this.ticketNumber,

    required this.issuedAt,

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
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionTicketStatusEnum.unknownDefaultOpenApi,
  )
  final AdmissionTicketStatusEnum status;

  @JsonKey(name: r'can_transfer', required: true, includeIfNull: false)
  final bool canTransfer;

  @JsonKey(name: r'event', required: true, includeIfNull: false)
  final AdmissionEvent event;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'type_id', required: true, includeIfNull: false)
  final int typeId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionTicketKindEnum.unknownDefaultOpenApi,
  )
  final AdmissionTicketKindEnum kind;

  @JsonKey(
    name: r'transfer_rule',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionTicketTransferRuleEnum.unknownDefaultOpenApi,
  )
  final AdmissionTicketTransferRuleEnum transferRule;

  @JsonKey(name: r'product_name', required: true, includeIfNull: false)
  final String productName;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'unit_price_cents', required: true, includeIfNull: false)
  final int unitPriceCents;

  @JsonKey(name: r'order_number', required: true, includeIfNull: false)
  final String orderNumber;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'named_learner_id', required: true, includeIfNull: true)
  final int? namedLearnerId;

  @JsonKey(name: r'named_learner_name', required: true, includeIfNull: true)
  final String? namedLearnerName;

  @JsonKey(name: r'qr', required: true, includeIfNull: true)
  final String? qr;

  @JsonKey(name: r'ticket_number', required: true, includeIfNull: true)
  final String? ticketNumber;

  @JsonKey(name: r'issued_at', required: true, includeIfNull: false)
  final DateTime issuedAt;

  @JsonKey(name: r'redeemed_at', required: true, includeIfNull: true)
  final DateTime? redeemedAt;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'redeemed_by', required: true, includeIfNull: true)
  final int? redeemedBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTicket &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.id == id &&
          other.holderUserId == holderUserId &&
          other.revision == revision &&
          other.status == status &&
          other.canTransfer == canTransfer &&
          other.event == event &&
          other.typeId == typeId &&
          other.kind == kind &&
          other.transferRule == transferRule &&
          other.productName == productName &&
          other.currency == currency &&
          other.unitPriceCents == unitPriceCents &&
          other.orderNumber == orderNumber &&
          other.namedLearnerId == namedLearnerId &&
          other.namedLearnerName == namedLearnerName &&
          other.qr == qr &&
          other.ticketNumber == ticketNumber &&
          other.issuedAt == issuedAt &&
          other.redeemedAt == redeemedAt &&
          other.redeemedBy == redeemedBy;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      id.hashCode +
      holderUserId.hashCode +
      revision.hashCode +
      status.hashCode +
      canTransfer.hashCode +
      event.hashCode +
      typeId.hashCode +
      kind.hashCode +
      transferRule.hashCode +
      productName.hashCode +
      currency.hashCode +
      unitPriceCents.hashCode +
      orderNumber.hashCode +
      (namedLearnerId == null ? 0 : namedLearnerId.hashCode) +
      (namedLearnerName == null ? 0 : namedLearnerName.hashCode) +
      (qr == null ? 0 : qr.hashCode) +
      (ticketNumber == null ? 0 : ticketNumber.hashCode) +
      issuedAt.hashCode +
      (redeemedAt == null ? 0 : redeemedAt.hashCode) +
      (redeemedBy == null ? 0 : redeemedBy.hashCode);

  factory AdmissionTicket.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTicketFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTicketToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionTicketStatusEnum {
  @JsonValue(r'valid')
  valid(r'valid'),
  @JsonValue(r'redeemed')
  redeemed(r'redeemed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionTicketStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AdmissionTicketKindEnum {
  @JsonValue(r'general')
  general(r'general'),
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionTicketKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AdmissionTicketTransferRuleEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'families')
  families(r'families'),
  @JsonValue(r'wallet_profiles')
  walletProfiles(r'wallet_profiles'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionTicketTransferRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
