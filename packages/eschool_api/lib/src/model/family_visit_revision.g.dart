// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_revision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitRevision _$FamilyVisitRevisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyVisitRevision', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
      final val = FamilyVisitRevision(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$FamilyVisitRevisionToJson(
  FamilyVisitRevision instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
