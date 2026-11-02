// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_inbox_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationInboxPage _$NotificationInboxPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'NotificationInboxPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'items',
        'page',
        'last_page',
        'total',
      ],
    );
    final val = NotificationInboxPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => NotificationInboxItem.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$NotificationInboxPageToJson(
  NotificationInboxPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'total': instance.total,
};
