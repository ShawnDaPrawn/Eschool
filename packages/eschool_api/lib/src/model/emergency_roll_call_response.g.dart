// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallResponse _$EmergencyRollCallResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyRollCallResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EmergencyRollCallResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EmergencyRollCall.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$EmergencyRollCallResponseCodeEnumEnumMap,
        v,
        unknownValue: EmergencyRollCallResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$EmergencyRollCallResponseToJson(
  EmergencyRollCallResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$EmergencyRollCallResponseCodeEnumEnumMap[instance.code]!,
};

const _$EmergencyRollCallResponseCodeEnumEnumMap = {
  EmergencyRollCallResponseCodeEnum.number200: 200,
  EmergencyRollCallResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
