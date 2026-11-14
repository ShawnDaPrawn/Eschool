// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_adjudicators_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAdjudicatorsWrite _$AcademicAdjudicatorsWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAdjudicatorsWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'adjudicators']);
  final val = AcademicAdjudicatorsWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    adjudicators: $checkedConvert(
      'adjudicators',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicAdjudicatorsWriteAdjudicatorsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$AcademicAdjudicatorsWriteToJson(
  AcademicAdjudicatorsWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'adjudicators': instance.adjudicators.map((e) => e.toJson()).toList(),
};
