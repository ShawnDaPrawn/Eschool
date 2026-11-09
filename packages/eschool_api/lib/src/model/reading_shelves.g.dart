// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_shelves.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingShelves _$ReadingShelvesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingShelves', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['grade_set', 'shelves', 'reading', 'ai_checks'],
      );
      final val = ReadingShelves(
        gradeSet: $checkedConvert('grade_set', (v) => v as bool),
        shelves: $checkedConvert(
          'shelves',
          (v) => (v as List<dynamic>)
              .map((e) => ReadingShelf.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        reading: $checkedConvert(
          'reading',
          (v) => ReadingWeek.fromJson(v as Map<String, dynamic>),
        ),
        aiChecks: $checkedConvert('ai_checks', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'gradeSet': 'grade_set', 'aiChecks': 'ai_checks'});

Map<String, dynamic> _$ReadingShelvesToJson(ReadingShelves instance) =>
    <String, dynamic>{
      'grade_set': instance.gradeSet,
      'shelves': instance.shelves.map((e) => e.toJson()).toList(),
      'reading': instance.reading.toJson(),
      'ai_checks': instance.aiChecks,
    };
