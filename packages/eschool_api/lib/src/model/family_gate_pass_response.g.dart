// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_pass_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGatePassResponse _$FamilyGatePassResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyGatePassResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyGatePassResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyGatePass.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyGatePassResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyGatePassResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyGatePassResponseToJson(
  FamilyGatePassResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyGatePassResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyGatePassResponseCodeEnumEnumMap = {
  FamilyGatePassResponseCodeEnum.number200: 200,
  FamilyGatePassResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
