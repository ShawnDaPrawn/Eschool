// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'read_staff_private_notice_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadStaffPrivateNoticeResponse _$ReadStaffPrivateNoticeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadStaffPrivateNoticeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReadStaffPrivateNoticeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => StaffPrivateNoticeRead.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadStaffPrivateNoticeResponseToJson(
  ReadStaffPrivateNoticeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
