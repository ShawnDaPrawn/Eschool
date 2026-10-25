// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeAccessResponse _$MyGradeAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MyGradeAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = MyGradeAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => MyGradeAccess.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$MyGradeAccessResponseToJson(
  MyGradeAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
