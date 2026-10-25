// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_framework_view_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFrameworkViewResponse _$DisciplineFrameworkViewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineFrameworkViewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineFrameworkViewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineFrameworkView.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineFrameworkViewResponseToJson(
  DisciplineFrameworkViewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
