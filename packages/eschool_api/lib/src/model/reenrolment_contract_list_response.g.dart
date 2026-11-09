// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractListResponse _$ReenrolmentContractListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentContractListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentContractList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentContractListResponseCodeEnumEnumMap,
        v,
        unknownValue:
            ReenrolmentContractListResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentContractListResponseToJson(
  ReenrolmentContractListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentContractListResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentContractListResponseCodeEnumEnumMap = {
  ReenrolmentContractListResponseCodeEnum.number200: 200,
  ReenrolmentContractListResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
