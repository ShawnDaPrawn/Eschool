// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_class.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallClass _$EmergencyRollCallClassFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyRollCallClass', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'class_section_id',
      'name',
      'accounted',
      'unaccounted',
      'released',
      'learners',
    ],
  );
  final val = EmergencyRollCallClass(
    classSectionId: $checkedConvert(
      'class_section_id',
      (v) => (v as num).toInt(),
    ),
    name: $checkedConvert('name', (v) => v as String),
    accounted: $checkedConvert('accounted', (v) => (v as num).toInt()),
    unaccounted: $checkedConvert('unaccounted', (v) => (v as num).toInt()),
    released: $checkedConvert('released', (v) => (v as num).toInt()),
    learners: $checkedConvert(
      'learners',
      (v) => (v as List<dynamic>)
          .map(
            (e) => EmergencyRollCallLearner.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'classSectionId': 'class_section_id'});

Map<String, dynamic> _$EmergencyRollCallClassToJson(
  EmergencyRollCallClass instance,
) => <String, dynamic>{
  'class_section_id': instance.classSectionId,
  'name': instance.name,
  'accounted': instance.accounted,
  'unaccounted': instance.unaccounted,
  'released': instance.released,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
