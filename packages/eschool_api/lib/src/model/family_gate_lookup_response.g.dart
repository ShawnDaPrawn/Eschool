// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_lookup_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateLookupResponse _$FamilyGateLookupResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyGateLookupResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyGateLookupResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyGateLookup.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyGateLookupResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyGateLookupResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyGateLookupResponseToJson(
  FamilyGateLookupResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyGateLookupResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyGateLookupResponseCodeEnumEnumMap = {
  FamilyGateLookupResponseCodeEnum.number200: 200,
  FamilyGateLookupResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
