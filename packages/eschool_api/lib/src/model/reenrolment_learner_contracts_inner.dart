//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_learner_contracts_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentLearnerContractsInner {
  /// Returns a new [ReenrolmentLearnerContractsInner] instance.
  ReenrolmentLearnerContractsInner({
    required this.id,

    required this.sessionYear,

    required this.version,

    required this.title,

    required this.body,

    required this.textHash,

    required this.acceptanceRule,

    required this.respondBy,

    required this.status,

    required this.youAreFeeResponsible,

    required this.youAcceptedAt,

    required this.canAccept,

    required this.waitingForFeePayer,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'session_year', required: true, includeIfNull: false)
  final String sessionYear;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'text_hash', required: true, includeIfNull: false)
  final String textHash;

  @JsonKey(name: r'acceptance_rule', required: true, includeIfNull: false)
  final String acceptanceRule;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ReenrolmentLearnerContractsInnerStatusEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentLearnerContractsInnerStatusEnum status;

  @JsonKey(
    name: r'you_are_fee_responsible',
    required: true,
    includeIfNull: false,
  )
  final bool youAreFeeResponsible;

  @JsonKey(name: r'you_accepted_at', required: true, includeIfNull: true)
  final DateTime? youAcceptedAt;

  @JsonKey(name: r'can_accept', required: true, includeIfNull: false)
  final bool canAccept;

  @JsonKey(name: r'waiting_for_fee_payer', required: true, includeIfNull: false)
  final bool waitingForFeePayer;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentLearnerContractsInner &&
          other.id == id &&
          other.sessionYear == sessionYear &&
          other.version == version &&
          other.title == title &&
          other.body == body &&
          other.textHash == textHash &&
          other.acceptanceRule == acceptanceRule &&
          other.respondBy == respondBy &&
          other.status == status &&
          other.youAreFeeResponsible == youAreFeeResponsible &&
          other.youAcceptedAt == youAcceptedAt &&
          other.canAccept == canAccept &&
          other.waitingForFeePayer == waitingForFeePayer;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYear.hashCode +
      version.hashCode +
      title.hashCode +
      body.hashCode +
      textHash.hashCode +
      acceptanceRule.hashCode +
      respondBy.hashCode +
      status.hashCode +
      youAreFeeResponsible.hashCode +
      (youAcceptedAt == null ? 0 : youAcceptedAt.hashCode) +
      canAccept.hashCode +
      waitingForFeePayer.hashCode;

  factory ReenrolmentLearnerContractsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$ReenrolmentLearnerContractsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReenrolmentLearnerContractsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentLearnerContractsInnerStatusEnum {
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'awaiting')
  awaiting(r'awaiting'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReenrolmentLearnerContractsInnerStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
