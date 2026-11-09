// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_reminded_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentRemindedResponse _$ReenrolmentRemindedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentRemindedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentRemindedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentReminded.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentRemindedResponseCodeEnumEnumMap,
        v,
        unknownValue: ReenrolmentRemindedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentRemindedResponseToJson(
  ReenrolmentRemindedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentRemindedResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentRemindedResponseCodeEnumEnumMap = {
  ReenrolmentRemindedResponseCodeEnum.number200: 200,
  ReenrolmentRemindedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
