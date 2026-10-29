//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_counter_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_resolution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterResolution {
  /// Returns a new [VendorCounterResolution] instance.
  VendorCounterResolution({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.operationId,

    required this.status,

    required this.order,
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
    unknownEnumValue: VendorCounterResolutionStatusEnum.unknownDefaultOpenApi,
  )
  final VendorCounterResolutionStatusEnum status;

  @JsonKey(name: r'order', required: true, includeIfNull: true)
  final VendorCounterReceipt? order;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorCounterResolution &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.operationId == operationId &&
          other.status == status &&
          other.order == order;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      operationId.hashCode +
      status.hashCode +
      (order == null ? 0 : order.hashCode);

  factory VendorCounterResolution.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterResolutionFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterResolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum VendorCounterResolutionStatusEnum {
  @JsonValue(r'committed')
  committed(r'committed'),
  @JsonValue(r'abandoned')
  abandoned(r'abandoned'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorCounterResolutionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
