// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayChoice _$PathwayChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PathwayChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = PathwayChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PathwayChoiceToJson(PathwayChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
