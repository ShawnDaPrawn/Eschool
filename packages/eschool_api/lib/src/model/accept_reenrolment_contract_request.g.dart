// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'accept_reenrolment_contract_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcceptReenrolmentContractRequest _$AcceptReenrolmentContractRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcceptReenrolmentContractRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'contract_id',
        'version',
        'text_hash',
        'signed_name',
        'acknowledged',
      ],
    );
    final val = AcceptReenrolmentContractRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      contractId: $checkedConvert('contract_id', (v) => (v as num).toInt()),
      version: $checkedConvert('version', (v) => (v as num).toInt()),
      textHash: $checkedConvert('text_hash', (v) => v as String),
      signedName: $checkedConvert('signed_name', (v) => v as String),
      acknowledged: $checkedConvert(
        'acknowledged',
        (v) => AcceptReenrolmentContractRequestAcknowledgedEnum.fromJson(v),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'contractId': 'contract_id',
    'textHash': 'text_hash',
    'signedName': 'signed_name',
  },
);

Map<String, dynamic> _$AcceptReenrolmentContractRequestToJson(
  AcceptReenrolmentContractRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'contract_id': instance.contractId,
  'version': instance.version,
  'text_hash': instance.textHash,
  'signed_name': instance.signedName,
  'acknowledged': ?AcceptReenrolmentContractRequestAcknowledgedEnum.toJsonValue(
    instance.acknowledged,
  ),
};
