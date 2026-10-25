// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'staff_private_notice_read.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StaffPrivateNoticeRead _$StaffPrivateNoticeReadFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'StaffPrivateNoticeRead',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'school_id', 'reader_id', 'read_at'],
    );
    final val = StaffPrivateNoticeRead(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      readAt: $checkedConvert('read_at', (v) => DateTime.parse(v as String)),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'readAt': 'read_at',
  },
);

Map<String, dynamic> _$StaffPrivateNoticeReadToJson(
  StaffPrivateNoticeRead instance,
) => <String, dynamic>{
  'id': instance.id,
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'read_at': instance.readAt.toIso8601String(),
};
