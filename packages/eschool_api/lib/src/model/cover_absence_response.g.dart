// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_absence_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverAbsenceResponse _$CoverAbsenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CoverAbsenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CoverAbsenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CoverAbsence.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CoverAbsenceResponseToJson(
  CoverAbsenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
