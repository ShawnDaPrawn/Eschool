// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_learner_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionLearnerPage _$RecognitionLearnerPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionLearnerPage', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'next_after']);
  final val = RecognitionLearnerPage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => RecognitionLearner.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    nextAfter: $checkedConvert('next_after', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'nextAfter': 'next_after'});

Map<String, dynamic> _$RecognitionLearnerPageToJson(
  RecognitionLearnerPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_after': instance.nextAfter,
};
