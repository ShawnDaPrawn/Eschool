// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_settings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSettingsResponse _$RiskSettingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskSettingsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskSettingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskSettings.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskSettingsResponseToJson(
  RiskSettingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
