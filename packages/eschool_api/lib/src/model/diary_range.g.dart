// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_range.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryRange _$DiaryRangeFromJson(Map<String, dynamic> json) => $checkedCreate(
  'DiaryRange',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'today',
        'from',
        'to',
        'entries',
        'school_items',
      ],
    );
    final val = DiaryRange(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      today: $checkedConvert('today', (v) => DateTime.parse(v as String)),
      from: $checkedConvert('from', (v) => DateTime.parse(v as String)),
      to: $checkedConvert('to', (v) => DateTime.parse(v as String)),
      entries: $checkedConvert(
        'entries',
        (v) => (v as List<dynamic>)
            .map((e) => DiaryEntry.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      schoolItems: $checkedConvert(
        'school_items',
        (v) => (v as List<dynamic>)
            .map((e) => DiarySchoolItem.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'schoolItems': 'school_items',
  },
);

Map<String, dynamic> _$DiaryRangeToJson(DiaryRange instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'today': instance.today.toIso8601String(),
      'from': instance.from.toIso8601String(),
      'to': instance.to.toIso8601String(),
      'entries': instance.entries.map((e) => e.toJson()).toList(),
      'school_items': instance.schoolItems.map((e) => e.toJson()).toList(),
    };
