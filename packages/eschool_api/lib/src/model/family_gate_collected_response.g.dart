// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_collected_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateCollectedResponse _$FamilyGateCollectedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyGateCollectedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyGateCollectedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyGateCollected.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyGateCollectedResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyGateCollectedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyGateCollectedResponseToJson(
  FamilyGateCollectedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyGateCollectedResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyGateCollectedResponseCodeEnumEnumMap = {
  FamilyGateCollectedResponseCodeEnum.number200: 200,
  FamilyGateCollectedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
