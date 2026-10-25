// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_reference_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestReferenceResponse _$WeeklyDigestReferenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestReferenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = WeeklyDigestReferenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => WeeklyDigestReference.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestReferenceResponseToJson(
  WeeklyDigestReferenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
