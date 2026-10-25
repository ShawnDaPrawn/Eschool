// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_award_profile.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAwardProfile _$RecognitionAwardProfileFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionAwardProfile', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'next_before']);
  final val = RecognitionAwardProfile(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                RecognitionPermanentAward.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$RecognitionAwardProfileToJson(
  RecognitionAwardProfile instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_before': instance.nextBefore,
};
