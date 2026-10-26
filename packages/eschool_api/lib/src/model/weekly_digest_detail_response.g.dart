// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestDetailResponse _$WeeklyDigestDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = WeeklyDigestDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => WeeklyDigestDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestDetailResponseToJson(
  WeeklyDigestDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
