// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_house_counters.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionHouseCounters _$RecognitionHouseCountersFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionHouseCounters', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['session_year_id', 'revision', 'items'],
  );
  final val = RecognitionHouseCounters(
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => RecognitionHouseCounter.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$RecognitionHouseCountersToJson(
  RecognitionHouseCounters instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'revision': instance.revision,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
