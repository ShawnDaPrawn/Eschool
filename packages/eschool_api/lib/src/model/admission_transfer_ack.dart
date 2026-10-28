//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_transfer_ack.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTransferAck {
  /// Returns a new [AdmissionTransferAck] instance.
  AdmissionTransferAck({
    required this.schoolId,

    required this.actorUserId,

    required this.ticketId,

    required this.operationId,

    required this.revision,

    required this.status,
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
  @JsonKey(name: r'ticket_id', required: true, includeIfNull: false)
  final int ticketId;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionTransferAckStatusEnum.unknownDefaultOpenApi,
  )
  final AdmissionTransferAckStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTransferAck &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.ticketId == ticketId &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.status == status;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      ticketId.hashCode +
      operationId.hashCode +
      revision.hashCode +
      status.hashCode;

  factory AdmissionTransferAck.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTransferAckFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTransferAckToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionTransferAckStatusEnum {
  @JsonValue(r'transferred')
  transferred(r'transferred'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionTransferAckStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
