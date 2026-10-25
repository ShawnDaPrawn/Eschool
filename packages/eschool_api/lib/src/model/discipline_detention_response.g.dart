// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionResponse _$DisciplineDetentionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineDetentionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineDetentionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineDetention.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineDetentionResponseToJson(
  DisciplineDetentionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
