//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'accept_reenrolment_contract_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcceptReenrolmentContractRequest {
  /// Returns a new [AcceptReenrolmentContractRequest] instance.
  AcceptReenrolmentContractRequest({
    required this.operationId,

    required this.contractId,

    required this.version,

    required this.textHash,

    required this.signedName,

    required this.acknowledged,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'contract_id', required: true, includeIfNull: false)
  final int contractId;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'text_hash', required: true, includeIfNull: false)
  final String textHash;

  @JsonKey(name: r'signed_name', required: true, includeIfNull: false)
  final String signedName;

  @JsonKey(
    name: r'acknowledged',
    required: true,
    includeIfNull: false,
    fromJson: AcceptReenrolmentContractRequestAcknowledgedEnum.fromJson,
    toJson: AcceptReenrolmentContractRequestAcknowledgedEnum.toJsonValue,
  )
  final AcceptReenrolmentContractRequestAcknowledgedEnum acknowledged;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcceptReenrolmentContractRequest &&
          other.operationId == operationId &&
          other.contractId == contractId &&
          other.version == version &&
          other.textHash == textHash &&
          other.signedName == signedName &&
          other.acknowledged == acknowledged;

  @override
  int get hashCode =>
      operationId.hashCode +
      contractId.hashCode +
      version.hashCode +
      textHash.hashCode +
      signedName.hashCode +
      acknowledged.hashCode;

  factory AcceptReenrolmentContractRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$AcceptReenrolmentContractRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcceptReenrolmentContractRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcceptReenrolmentContractRequestAcknowledgedEnum {
  true_(true),
  unknownDefaultOpenApi(null);

  const AcceptReenrolmentContractRequestAcknowledgedEnum(this.value);
  final bool? value;

  static AcceptReenrolmentContractRequestAcknowledgedEnum fromJson(
    Object? value,
  ) => values.firstWhere(
    (entry) => entry.value != null && entry.value == value,
    orElse: () =>
        AcceptReenrolmentContractRequestAcknowledgedEnum.unknownDefaultOpenApi,
  );
  static bool? toJsonValue(
    AcceptReenrolmentContractRequestAcknowledgedEnum value,
  ) => value.value;
}
