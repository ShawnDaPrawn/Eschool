// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_staff_private_notices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListStaffPrivateNoticesResponse _$ListStaffPrivateNoticesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListStaffPrivateNoticesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListStaffPrivateNoticesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => StaffPrivateNoticePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListStaffPrivateNoticesResponseToJson(
  ListStaffPrivateNoticesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
