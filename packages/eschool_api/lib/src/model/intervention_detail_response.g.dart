// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionDetailResponse _$InterventionDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('InterventionDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = InterventionDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => InterventionDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$InterventionDetailResponseToJson(
  InterventionDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
