// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_book_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingBookCard _$ReadingBookCardFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ReadingBookCard',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'title',
            'author',
            'language',
            'subjects',
            'reading_level',
            'reading_level_label',
            'page_count',
            'progress',
          ],
        );
        final val = ReadingBookCard(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          author: $checkedConvert('author', (v) => v as String),
          language: $checkedConvert('language', (v) => v as String),
          subjects: $checkedConvert(
            'subjects',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          readingLevel: $checkedConvert(
            'reading_level',
            (v) => $enumDecode(
              _$ReadingBookCardReadingLevelEnumEnumMap,
              v,
              unknownValue:
                  ReadingBookCardReadingLevelEnum.unknownDefaultOpenApi,
            ),
          ),
          readingLevelLabel: $checkedConvert(
            'reading_level_label',
            (v) => v as String,
          ),
          pageCount: $checkedConvert('page_count', (v) => (v as num?)?.toInt()),
          progress: $checkedConvert(
            'progress',
            (v) => v == null
                ? null
                : ReadingProgress.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'readingLevel': 'reading_level',
        'readingLevelLabel': 'reading_level_label',
        'pageCount': 'page_count',
      },
    );

Map<String, dynamic> _$ReadingBookCardToJson(ReadingBookCard instance) =>
    <String, dynamic>{
      'id': instance.id,
      'title': instance.title,
      'author': instance.author,
      'language': instance.language,
      'subjects': instance.subjects,
      'reading_level':
          _$ReadingBookCardReadingLevelEnumEnumMap[instance.readingLevel]!,
      'reading_level_label': instance.readingLevelLabel,
      'page_count': instance.pageCount,
      'progress': instance.progress?.toJson(),
    };

const _$ReadingBookCardReadingLevelEnumEnumMap = {
  ReadingBookCardReadingLevelEnum.beginner: 'beginner',
  ReadingBookCardReadingLevelEnum.developing: 'developing',
  ReadingBookCardReadingLevelEnum.confident: 'confident',
  ReadingBookCardReadingLevelEnum.fluent: 'fluent',
  ReadingBookCardReadingLevelEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
