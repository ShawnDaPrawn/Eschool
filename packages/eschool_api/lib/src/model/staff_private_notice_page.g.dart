// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'staff_private_notice_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StaffPrivateNoticePage _$StaffPrivateNoticePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'StaffPrivateNoticePage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'writable',
        'items',
        'page',
        'last_page',
        'unread_count',
      ],
    );
    final val = StaffPrivateNoticePage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      writable: $checkedConvert('writable', (v) => v as bool),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => StaffPrivateNotice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      unreadCount: $checkedConvert('unread_count', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'lastPage': 'last_page',
    'unreadCount': 'unread_count',
  },
);

Map<String, dynamic> _$StaffPrivateNoticePageToJson(
  StaffPrivateNoticePage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'writable': instance.writable,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'unread_count': instance.unreadCount,
};
