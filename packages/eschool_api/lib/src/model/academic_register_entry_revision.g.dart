// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_entry_revision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterEntryRevision _$AcademicRegisterEntryRevisionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterEntryRevision', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'revision']);
  final val = AcademicRegisterEntryRevision(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$AcademicRegisterEntryRevisionToJson(
  AcademicRegisterEntryRevision instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'revision': instance.revision,
};
