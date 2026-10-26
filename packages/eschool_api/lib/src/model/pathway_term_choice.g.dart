// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_term_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayTermChoice _$PathwayTermChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PathwayTermChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'session_year_id']);
      final val = PathwayTermChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        sessionYearId: $checkedConvert(
          'session_year_id',
          (v) => (v as num).toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$PathwayTermChoiceToJson(PathwayTermChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'session_year_id': instance.sessionYearId,
    };
