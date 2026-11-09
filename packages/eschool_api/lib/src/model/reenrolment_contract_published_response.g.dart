// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_published_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractPublishedResponse
_$ReenrolmentContractPublishedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractPublishedResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentContractPublishedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentContractPublished.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentContractPublishedResponseCodeEnumEnumMap,
        v,
        unknownValue:
            ReenrolmentContractPublishedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentContractPublishedResponseToJson(
  ReenrolmentContractPublishedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentContractPublishedResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentContractPublishedResponseCodeEnumEnumMap = {
  ReenrolmentContractPublishedResponseCodeEnum.number200: 200,
  ReenrolmentContractPublishedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
