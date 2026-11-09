// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_decision_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryDecisionResponse _$BursaryDecisionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryDecisionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryDecisionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryDecision.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryDecisionResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryDecisionResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryDecisionResponseToJson(
  BursaryDecisionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryDecisionResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryDecisionResponseCodeEnumEnumMap = {
  BursaryDecisionResponseCodeEnum.number200: 200,
  BursaryDecisionResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
