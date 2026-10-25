// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_framework_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFrameworkResponse _$DisciplineFrameworkResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineFrameworkResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineFrameworkResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineFramework.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineFrameworkResponseToJson(
  DisciplineFrameworkResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
