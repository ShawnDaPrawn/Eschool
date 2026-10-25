// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_classroom_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskClassroomResponse _$RiskClassroomResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskClassroomResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskClassroomResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskClassroom.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskClassroomResponseToJson(
  RiskClassroomResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
