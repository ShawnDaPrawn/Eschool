// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_attendance_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityAttendanceReceiptResponse _$ActivityAttendanceReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityAttendanceReceiptResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityAttendanceReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityAttendanceReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityAttendanceReceiptResponseToJson(
  ActivityAttendanceReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
