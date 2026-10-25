// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominationPage _$RecognitionNominationPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionNominationPage', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'next_before']);
  final val = RecognitionNominationPage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => RecognitionNomination.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$RecognitionNominationPageToJson(
  RecognitionNominationPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_before': instance.nextBefore,
};
