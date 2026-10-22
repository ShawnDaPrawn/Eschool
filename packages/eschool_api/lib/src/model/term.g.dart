// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Term _$TermFromJson(Map<String, dynamic> json) =>
    $checkedCreate('Term', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'start_date', 'end_date', 'current'],
      );
      final val = Term(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        startDate: $checkedConvert(
          'start_date',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
        endDate: $checkedConvert(
          'end_date',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
        current: $checkedConvert('current', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'startDate': 'start_date', 'endDate': 'end_date'});

Map<String, dynamic> _$TermToJson(Term instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'start_date': instance.startDate?.toIso8601String(),
  'end_date': instance.endDate?.toIso8601String(),
  'current': instance.current,
};
