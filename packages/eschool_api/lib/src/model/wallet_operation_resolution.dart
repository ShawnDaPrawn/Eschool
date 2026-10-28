//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_operation_resolution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletOperationResolution {
  /// Returns a new [WalletOperationResolution] instance.
  WalletOperationResolution({
    required this.schoolId,

    required this.actorUserId,

    required this.operationId,

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

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletOperationResolutionStatusEnum.unknownDefaultOpenApi,
  )
  final WalletOperationResolutionStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletOperationResolution &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.operationId == operationId &&
          other.status == status;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      operationId.hashCode +
      status.hashCode;

  factory WalletOperationResolution.fromJson(Map<String, dynamic> json) =>
      _$WalletOperationResolutionFromJson(json);

  Map<String, dynamic> toJson() => _$WalletOperationResolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletOperationResolutionStatusEnum {
  @JsonValue(r'committed')
  committed(r'committed'),
  @JsonValue(r'abandoned')
  abandoned(r'abandoned'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletOperationResolutionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
