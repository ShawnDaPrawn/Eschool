// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingPage _$ReadingPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingPage', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['book_id', 'page', 'page_count', 'text'],
      );
      final val = ReadingPage(
        bookId: $checkedConvert('book_id', (v) => (v as num).toInt()),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        pageCount: $checkedConvert('page_count', (v) => (v as num).toInt()),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'bookId': 'book_id', 'pageCount': 'page_count'});

Map<String, dynamic> _$ReadingPageToJson(ReadingPage instance) =>
    <String, dynamic>{
      'book_id': instance.bookId,
      'page': instance.page,
      'page_count': instance.pageCount,
      'text': instance.text,
    };
