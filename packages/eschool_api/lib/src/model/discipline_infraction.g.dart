// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_infraction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineInfraction _$DisciplineInfractionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineInfraction', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['code', 'name', 'base_level']);
  final val = DisciplineInfraction(
    code: $checkedConvert('code', (v) => v as String),
    name: $checkedConvert('name', (v) => v as String),
    baseLevel: $checkedConvert('base_level', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'baseLevel': 'base_level'});

Map<String, dynamic> _$DisciplineInfractionToJson(
  DisciplineInfraction instance,
) => <String, dynamic>{
  'code': instance.code,
  'name': instance.name,
  'base_level': instance.baseLevel,
};
