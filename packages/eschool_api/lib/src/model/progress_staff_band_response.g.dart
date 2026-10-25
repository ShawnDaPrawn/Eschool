// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_staff_band_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressStaffBandResponse _$ProgressStaffBandResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressStaffBandResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressStaffBandResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressStaffBand.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressStaffBandResponseToJson(
  ProgressStaffBandResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
