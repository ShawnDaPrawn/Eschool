//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_counter_ticket.dart';
import 'package:json_annotation/json_annotation.dart';

part 'admission_counter_resolution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionCounterResolution {
  /// Returns a new [AdmissionCounterResolution] instance.
  AdmissionCounterResolution({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.operationId,

    required this.status,

    required this.receipt,
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

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        AdmissionCounterResolutionStatusEnum.unknownDefaultOpenApi,
  )
  final AdmissionCounterResolutionStatusEnum status;

  @JsonKey(name: r'receipt', required: true, includeIfNull: true)
  final AdmissionCounterTicket? receipt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionCounterResolution &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.operationId == operationId &&
          other.status == status &&
          other.receipt == receipt;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      operationId.hashCode +
      status.hashCode +
      (receipt == null ? 0 : receipt.hashCode);

  factory AdmissionCounterResolution.fromJson(Map<String, dynamic> json) =>
      _$AdmissionCounterResolutionFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionCounterResolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionCounterResolutionStatusEnum {
  @JsonValue(r'committed')
  committed(r'committed'),
  @JsonValue(r'abandoned')
  abandoned(r'abandoned'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionCounterResolutionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
