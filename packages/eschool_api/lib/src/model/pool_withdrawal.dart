//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_withdrawal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolWithdrawal {
  /// Returns a new [PoolWithdrawal] instance.
  PoolWithdrawal({required this.consentStatus, required this.withdrawnAt});

  @JsonKey(
    name: r'consent_status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolWithdrawalConsentStatusEnum.unknownDefaultOpenApi,
  )
  final PoolWithdrawalConsentStatusEnum consentStatus;

  @JsonKey(name: r'withdrawn_at', required: true, includeIfNull: true)
  final String? withdrawnAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolWithdrawal &&
          other.consentStatus == consentStatus &&
          other.withdrawnAt == withdrawnAt;

  @override
  int get hashCode =>
      consentStatus.hashCode + (withdrawnAt == null ? 0 : withdrawnAt.hashCode);

  factory PoolWithdrawal.fromJson(Map<String, dynamic> json) =>
      _$PoolWithdrawalFromJson(json);

  Map<String, dynamic> toJson() => _$PoolWithdrawalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolWithdrawalConsentStatusEnum {
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolWithdrawalConsentStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
