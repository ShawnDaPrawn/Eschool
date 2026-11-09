// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_marked_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallMarkedResponse _$EmergencyRollCallMarkedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyRollCallMarkedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EmergencyRollCallMarkedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EmergencyRollCallMarked.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$EmergencyRollCallMarkedResponseCodeEnumEnumMap,
        v,
        unknownValue:
            EmergencyRollCallMarkedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$EmergencyRollCallMarkedResponseToJson(
  EmergencyRollCallMarkedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$EmergencyRollCallMarkedResponseCodeEnumEnumMap[instance.code]!,
};

const _$EmergencyRollCallMarkedResponseCodeEnumEnumMap = {
  EmergencyRollCallMarkedResponseCodeEnum.number200: 200,
  EmergencyRollCallMarkedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
