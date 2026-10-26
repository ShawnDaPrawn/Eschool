// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingPage _$FamilyMeetingPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyMeetingPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'next_page']);
      final val = FamilyMeetingPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => FamilyMeetingSlot.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextPage': 'next_page'});

Map<String, dynamic> _$FamilyMeetingPageToJson(FamilyMeetingPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_page': instance.nextPage,
    };
