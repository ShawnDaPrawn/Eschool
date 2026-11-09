// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_reading_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportReadingItemsInner _$LearnerPassportReadingItemsInnerFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('LearnerPassportReadingItemsInner', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['title']);
      final val = LearnerPassportReadingItemsInner(
        title: $checkedConvert('title', (v) => v as String),
        author: $checkedConvert('author', (v) => v as String?),
        finishedOn: $checkedConvert(
          'finished_on',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'finishedOn': 'finished_on'});

Map<String, dynamic> _$LearnerPassportReadingItemsInnerToJson(
  LearnerPassportReadingItemsInner instance,
) => <String, dynamic>{
  'title': instance.title,
  'author': ?instance.author,
  'finished_on': ?instance.finishedOn?.toIso8601String(),
};
