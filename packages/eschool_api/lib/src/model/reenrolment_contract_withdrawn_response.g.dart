// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_withdrawn_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractWithdrawnResponse
_$ReenrolmentContractWithdrawnResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractWithdrawnResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentContractWithdrawnResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentContractWithdrawn.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentContractWithdrawnResponseCodeEnumEnumMap,
        v,
        unknownValue:
            ReenrolmentContractWithdrawnResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentContractWithdrawnResponseToJson(
  ReenrolmentContractWithdrawnResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentContractWithdrawnResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentContractWithdrawnResponseCodeEnumEnumMap = {
  ReenrolmentContractWithdrawnResponseCodeEnum.number200: 200,
  ReenrolmentContractWithdrawnResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
