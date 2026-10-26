// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_pass_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGatePassPageResponse _$FamilyGatePassPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyGatePassPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyGatePassPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyGatePassPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyGatePassPageResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyGatePassPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyGatePassPageResponseToJson(
  FamilyGatePassPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyGatePassPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyGatePassPageResponseCodeEnumEnumMap = {
  FamilyGatePassPageResponseCodeEnum.number200: 200,
  FamilyGatePassPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
