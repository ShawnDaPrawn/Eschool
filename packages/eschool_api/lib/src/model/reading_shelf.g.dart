// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_shelf.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingShelf _$ReadingShelfFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingShelf', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'description', 'books'],
      );
      final val = ReadingShelf(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        description: $checkedConvert('description', (v) => v as String?),
        books: $checkedConvert(
          'books',
          (v) => (v as List<dynamic>)
              .map((e) => ReadingBookCard.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ReadingShelfToJson(ReadingShelf instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'description': instance.description,
      'books': instance.books.map((e) => e.toJson()).toList(),
    };
