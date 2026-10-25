// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_year.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionYear _$RecognitionYearFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RecognitionYear', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'starts_on', 'ends_on'],
      );
      final val = RecognitionYear(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        startsOn: $checkedConvert('starts_on', (v) => v as String),
        endsOn: $checkedConvert('ends_on', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'startsOn': 'starts_on', 'endsOn': 'ends_on'});

Map<String, dynamic> _$RecognitionYearToJson(RecognitionYear instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
    };
