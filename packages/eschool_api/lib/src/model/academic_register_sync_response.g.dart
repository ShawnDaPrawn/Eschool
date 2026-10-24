// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_sync_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterSyncResponse _$AcademicRegisterSyncResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterSyncResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRegisterSyncResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRegisterSyncData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRegisterSyncResponseToJson(
  AcademicRegisterSyncResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
