// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_book.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingBook _$ReadingBookFromJson(Map<String, dynamic> json) => $checkedCreate(
  'ReadingBook',
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
        'author_birth_year',
        'author_death_year',
        'summary',
        'word_count',
        'checks',
      ],
    );
    final val = ReadingBook(
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
          _$ReadingBookReadingLevelEnumEnumMap,
          v,
          unknownValue: ReadingBookReadingLevelEnum.unknownDefaultOpenApi,
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
      authorBirthYear: $checkedConvert(
        'author_birth_year',
        (v) => (v as num?)?.toInt(),
      ),
      authorDeathYear: $checkedConvert(
        'author_death_year',
        (v) => (v as num?)?.toInt(),
      ),
      summary: $checkedConvert('summary', (v) => v as String?),
      wordCount: $checkedConvert('word_count', (v) => (v as num?)?.toInt()),
      checks: $checkedConvert(
        'checks',
        (v) => ReadingBookChecks.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'readingLevel': 'reading_level',
    'readingLevelLabel': 'reading_level_label',
    'pageCount': 'page_count',
    'authorBirthYear': 'author_birth_year',
    'authorDeathYear': 'author_death_year',
    'wordCount': 'word_count',
  },
);

Map<String, dynamic> _$ReadingBookToJson(
  ReadingBook instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'author': instance.author,
  'language': instance.language,
  'subjects': instance.subjects,
  'reading_level': _$ReadingBookReadingLevelEnumEnumMap[instance.readingLevel]!,
  'reading_level_label': instance.readingLevelLabel,
  'page_count': instance.pageCount,
  'progress': instance.progress?.toJson(),
  'author_birth_year': instance.authorBirthYear,
  'author_death_year': instance.authorDeathYear,
  'summary': instance.summary,
  'word_count': instance.wordCount,
  'checks': instance.checks.toJson(),
};

const _$ReadingBookReadingLevelEnumEnumMap = {
  ReadingBookReadingLevelEnum.beginner: 'beginner',
  ReadingBookReadingLevelEnum.developing: 'developing',
  ReadingBookReadingLevelEnum.confident: 'confident',
  ReadingBookReadingLevelEnum.fluent: 'fluent',
  ReadingBookReadingLevelEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
