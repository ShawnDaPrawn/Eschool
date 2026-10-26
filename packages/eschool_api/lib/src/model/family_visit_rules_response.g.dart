// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_rules_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitRulesResponse _$FamilyVisitRulesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyVisitRulesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyVisitRulesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyVisitRules.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyVisitRulesResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyVisitRulesResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyVisitRulesResponseToJson(
  FamilyVisitRulesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyVisitRulesResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyVisitRulesResponseCodeEnumEnumMap = {
  FamilyVisitRulesResponseCodeEnum.number200: 200,
  FamilyVisitRulesResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
