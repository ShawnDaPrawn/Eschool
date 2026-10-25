// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_professional_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskProfessionalChoicesResponse _$RiskProfessionalChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskProfessionalChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskProfessionalChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskProfessionalChoices.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskProfessionalChoicesResponseToJson(
  RiskProfessionalChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
