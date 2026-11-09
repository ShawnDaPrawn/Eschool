// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_book_checks.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingBookChecks _$ReadingBookChecksFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingBookChecks', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['total', 'open', 'answered']);
      final val = ReadingBookChecks(
        total: $checkedConvert('total', (v) => (v as num).toInt()),
        open: $checkedConvert('open', (v) => (v as num).toInt()),
        answered: $checkedConvert('answered', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ReadingBookChecksToJson(ReadingBookChecks instance) =>
    <String, dynamic>{
      'total': instance.total,
      'open': instance.open,
      'answered': instance.answered,
    };
