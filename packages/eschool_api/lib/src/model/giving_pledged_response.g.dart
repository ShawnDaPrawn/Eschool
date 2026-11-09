// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_pledged_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingPledgedResponse _$GivingPledgedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingPledgedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingPledgedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingPledged.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingPledgedResponseCodeEnumEnumMap,
        v,
        unknownValue: GivingPledgedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingPledgedResponseToJson(
  GivingPledgedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingPledgedResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingPledgedResponseCodeEnumEnumMap = {
  GivingPledgedResponseCodeEnum.number200: 200,
  GivingPledgedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
