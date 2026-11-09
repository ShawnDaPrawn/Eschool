// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_donors_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingDonorsResponse _$GivingDonorsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingDonorsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingDonorsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingDonors.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingDonorsResponseCodeEnumEnumMap,
        v,
        unknownValue: GivingDonorsResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingDonorsResponseToJson(
  GivingDonorsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingDonorsResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingDonorsResponseCodeEnumEnumMap = {
  GivingDonorsResponseCodeEnum.number200: 200,
  GivingDonorsResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
