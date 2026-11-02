// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_list_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedListResponseData _$CalendarFeedListResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarFeedListResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = CalendarFeedListResponseData(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => CalendarFeed.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$CalendarFeedListResponseDataToJson(
  CalendarFeedListResponseData instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
