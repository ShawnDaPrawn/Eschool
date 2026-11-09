// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_donated_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingDonatedResponse _$GivingDonatedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingDonatedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingDonatedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingDonated.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingDonatedResponseCodeEnumEnumMap,
        v,
        unknownValue: GivingDonatedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingDonatedResponseToJson(
  GivingDonatedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingDonatedResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingDonatedResponseCodeEnumEnumMap = {
  GivingDonatedResponseCodeEnum.number200: 200,
  GivingDonatedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
