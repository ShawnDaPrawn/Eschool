// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_teacher_disclosure.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportTeacherDisclosure _$SupportTeacherDisclosureFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SupportTeacherDisclosure',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'learner_name',
        'revision',
        'expires_on',
        'accommodations',
      ],
    );
    final val = SupportTeacherDisclosure(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      expiresOn: $checkedConvert('expires_on', (v) => v as String),
      accommodations: $checkedConvert('accommodations', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$SupportTeacherDisclosureToJson(
  SupportTeacherDisclosure instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'revision': instance.revision,
  'expires_on': instance.expiresOn,
  'accommodations': instance.accommodations,
};
