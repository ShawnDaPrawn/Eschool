// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryChange _$BursaryChangeFromJson(Map<String, dynamic> json) =>
    $checkedCreate('BursaryChange', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'state', 'revision']);
      final val = BursaryChange(
        id: $checkedConvert('id', (v) => v as String),
        state: $checkedConvert('state', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$BursaryChangeToJson(BursaryChange instance) =>
    <String, dynamic>{
      'id': instance.id,
      'state': instance.state,
      'revision': instance.revision,
    };
