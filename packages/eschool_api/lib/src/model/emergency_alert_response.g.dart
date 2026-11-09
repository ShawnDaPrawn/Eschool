// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_alert_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyAlertResponse _$EmergencyAlertResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyAlertResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EmergencyAlertResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EmergencyAlert.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$EmergencyAlertResponseCodeEnumEnumMap,
        v,
        unknownValue: EmergencyAlertResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$EmergencyAlertResponseToJson(
  EmergencyAlertResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$EmergencyAlertResponseCodeEnumEnumMap[instance.code]!,
};

const _$EmergencyAlertResponseCodeEnumEnumMap = {
  EmergencyAlertResponseCodeEnum.number200: 200,
  EmergencyAlertResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
