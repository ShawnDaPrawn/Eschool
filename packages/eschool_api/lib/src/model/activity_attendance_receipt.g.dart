// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_attendance_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityAttendanceReceipt _$ActivityAttendanceReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityAttendanceReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_revision', 'items', 'unrecorded_count'],
    );
    final val = ActivityAttendanceReceipt(
      sessionRevision: $checkedConvert(
        'session_revision',
        (v) => (v as num).toInt(),
      ),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => ActivityAttendance.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      unrecordedCount: $checkedConvert(
        'unrecorded_count',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionRevision': 'session_revision',
    'unrecordedCount': 'unrecorded_count',
  },
);

Map<String, dynamic> _$ActivityAttendanceReceiptToJson(
  ActivityAttendanceReceipt instance,
) => <String, dynamic>{
  'session_revision': instance.sessionRevision,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'unrecorded_count': instance.unrecordedCount,
};
