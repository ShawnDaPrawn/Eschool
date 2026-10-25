// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionDetailResponse _$DisciplineDetentionDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineDetentionDetailResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineDetentionDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineDetentionDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineDetentionDetailResponseToJson(
  DisciplineDetentionDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
