// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_qr_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterQrData _$AcademicRegisterQrDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterQrData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['qr_code']);
  final val = AcademicRegisterQrData(
    qrCode: $checkedConvert('qr_code', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'qrCode': 'qr_code'});

Map<String, dynamic> _$AcademicRegisterQrDataToJson(
  AcademicRegisterQrData instance,
) => <String, dynamic>{'qr_code': instance.qrCode};
