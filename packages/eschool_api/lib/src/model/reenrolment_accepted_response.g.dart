// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_accepted_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentAcceptedResponse _$ReenrolmentAcceptedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentAcceptedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentAcceptedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentAccepted.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentAcceptedResponseCodeEnumEnumMap,
        v,
        unknownValue: ReenrolmentAcceptedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentAcceptedResponseToJson(
  ReenrolmentAcceptedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentAcceptedResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentAcceptedResponseCodeEnumEnumMap = {
  ReenrolmentAcceptedResponseCodeEnum.number200: 200,
  ReenrolmentAcceptedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
