// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_dates.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermDates _$TermDatesFromJson(Map<String, dynamic> json) => $checkedCreate(
  'TermDates',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['name', 'starts_on', 'ends_on']);
    final val = TermDates(
      name: $checkedConvert('name', (v) => v as String),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      endsOn: $checkedConvert(
        'ends_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      gradeFrom: $checkedConvert('grade_from', (v) => (v as num?)?.toInt()),
      gradeTo: $checkedConvert('grade_to', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'gradeFrom': 'grade_from',
    'gradeTo': 'grade_to',
  },
);

Map<String, dynamic> _$TermDatesToJson(TermDates instance) => <String, dynamic>{
  'name': instance.name,
  'starts_on': instance.startsOn?.toIso8601String(),
  'ends_on': instance.endsOn?.toIso8601String(),
  'grade_from': ?instance.gradeFrom,
  'grade_to': ?instance.gradeTo,
};
