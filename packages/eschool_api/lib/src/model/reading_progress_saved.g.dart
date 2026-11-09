// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_progress_saved.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingProgressSaved _$ReadingProgressSavedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadingProgressSaved', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['progress', 'reading', 'badges']);
  final val = ReadingProgressSaved(
    progress: $checkedConvert(
      'progress',
      (v) => ReadingProgress.fromJson(v as Map<String, dynamic>),
    ),
    reading: $checkedConvert(
      'reading',
      (v) => ReadingWeek.fromJson(v as Map<String, dynamic>),
    ),
    badges: $checkedConvert(
      'badges',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReadingProgressSavedToJson(
  ReadingProgressSaved instance,
) => <String, dynamic>{
  'progress': instance.progress.toJson(),
  'reading': instance.reading.toJson(),
  'badges': instance.badges,
};
