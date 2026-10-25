// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordDetailResponse _$DisciplineRecordDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineRecordDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineRecordDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineRecordDetailResponseToJson(
  DisciplineRecordDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
