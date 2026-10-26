// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_cancelled_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitCancelledResponse _$FamilyVisitCancelledResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyVisitCancelledResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyVisitCancelledResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyVisitCancelled.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyVisitCancelledResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyVisitCancelledResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyVisitCancelledResponseToJson(
  FamilyVisitCancelledResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyVisitCancelledResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyVisitCancelledResponseCodeEnumEnumMap = {
  FamilyVisitCancelledResponseCodeEnum.number200: 200,
  FamilyVisitCancelledResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
