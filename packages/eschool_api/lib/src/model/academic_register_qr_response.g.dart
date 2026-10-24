// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_qr_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterQrResponse _$AcademicRegisterQrResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterQrResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRegisterQrResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRegisterQrData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRegisterQrResponseToJson(
  AcademicRegisterQrResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
