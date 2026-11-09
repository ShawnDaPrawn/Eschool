//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_accepted.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentAccepted {
  /// Returns a new [ReenrolmentAccepted] instance.
  ReenrolmentAccepted({
    required this.id,

    required this.contractId,

    required this.learnerId,

    required this.version,

    required this.acceptedAt,

    required this.status,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'contract_id', required: true, includeIfNull: false)
  final int contractId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'accepted_at', required: true, includeIfNull: false)
  final DateTime acceptedAt;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReenrolmentAcceptedStatusEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentAcceptedStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentAccepted &&
          other.id == id &&
          other.contractId == contractId &&
          other.learnerId == learnerId &&
          other.version == version &&
          other.acceptedAt == acceptedAt &&
          other.status == status;

  @override
  int get hashCode =>
      id.hashCode +
      contractId.hashCode +
      learnerId.hashCode +
      version.hashCode +
      acceptedAt.hashCode +
      status.hashCode;

  factory ReenrolmentAccepted.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentAcceptedFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentAcceptedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentAcceptedStatusEnum {
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'awaiting')
  awaiting(r'awaiting'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReenrolmentAcceptedStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
