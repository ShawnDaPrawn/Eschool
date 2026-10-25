// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_point_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPointHistory _$RecognitionPointHistoryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionPointHistory', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'next_before']);
  final val = RecognitionPointHistory(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => RecognitionPointEntry.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$RecognitionPointHistoryToJson(
  RecognitionPointHistory instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_before': instance.nextBefore,
};
