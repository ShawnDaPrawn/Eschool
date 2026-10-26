// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_elective_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayElectiveResult _$PathwayElectiveResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayElectiveResult', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['title', 'choose', 'selected', 'valid'],
  );
  final val = PathwayElectiveResult(
    title: $checkedConvert('title', (v) => v as String),
    choose: $checkedConvert('choose', (v) => (v as num).toInt()),
    selected: $checkedConvert('selected', (v) => (v as num).toInt()),
    valid: $checkedConvert('valid', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$PathwayElectiveResultToJson(
  PathwayElectiveResult instance,
) => <String, dynamic>{
  'title': instance.title,
  'choose': instance.choose,
  'selected': instance.selected,
  'valid': instance.valid,
};
