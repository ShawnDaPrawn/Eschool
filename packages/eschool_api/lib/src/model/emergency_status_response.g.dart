// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_status_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyStatusResponse _$EmergencyStatusResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyStatusResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EmergencyStatusResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EmergencyStatus.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$EmergencyStatusResponseCodeEnumEnumMap,
        v,
        unknownValue: EmergencyStatusResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$EmergencyStatusResponseToJson(
  EmergencyStatusResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$EmergencyStatusResponseCodeEnumEnumMap[instance.code]!,
};

const _$EmergencyStatusResponseCodeEnumEnumMap = {
  EmergencyStatusResponseCodeEnum.number200: 200,
  EmergencyStatusResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
