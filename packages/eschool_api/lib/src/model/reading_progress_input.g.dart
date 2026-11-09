// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_progress_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingProgressInput _$ReadingProgressInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadingProgressInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'seconds']);
  final val = ReadingProgressInput(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    seconds: $checkedConvert('seconds', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadingProgressInputToJson(
  ReadingProgressInput instance,
) => <String, dynamic>{'page': instance.page, 'seconds': instance.seconds};
