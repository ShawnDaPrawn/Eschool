// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_fact.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionFact _$AcademicTransitionFactFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionFact', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'kind', 'effective_on']);
  final val = AcademicTransitionFact(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    kind: $checkedConvert('kind', (v) => v as String),
    effectiveOn: $checkedConvert('effective_on', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'effectiveOn': 'effective_on'});

Map<String, dynamic> _$AcademicTransitionFactToJson(
  AcademicTransitionFact instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': instance.kind,
  'effective_on': instance.effectiveOn,
};
