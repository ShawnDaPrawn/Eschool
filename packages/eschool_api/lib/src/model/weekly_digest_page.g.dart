// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestPage _$WeeklyDigestPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WeeklyDigestPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'total', 'next_before']);
      final val = WeeklyDigestPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => WeeklyDigestListItem.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        total: $checkedConvert('total', (v) => (v as num).toInt()),
        nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$WeeklyDigestPageToJson(WeeklyDigestPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'total': instance.total,
      'next_before': instance.nextBefore,
    };
