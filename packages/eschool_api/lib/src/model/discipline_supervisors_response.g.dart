// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_supervisors_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineSupervisorsResponse _$DisciplineSupervisorsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineSupervisorsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineSupervisorsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineSupervisors.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineSupervisorsResponseToJson(
  DisciplineSupervisorsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
