// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_notice_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingNoticePage _$WellbeingNoticePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingNoticePage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['items', 'page', 'last_page', 'unread_count'],
  );
  final val = WellbeingNoticePage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => WellbeingNotice.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    unreadCount: $checkedConvert('unread_count', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page', 'unreadCount': 'unread_count'});

Map<String, dynamic> _$WellbeingNoticePageToJson(
  WellbeingNoticePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'unread_count': instance.unreadCount,
};
