// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterLearner _$AcademicRegisterLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'state',
        'revision',
        'source',
        'amended_at',
        'qr_code',
      ],
    );
    final val = AcademicRegisterLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      state: $checkedConvert('state', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      source_: $checkedConvert('source', (v) => v as String?),
      amendedAt: $checkedConvert('amended_at', (v) => v as String?),
      qrCode: $checkedConvert('qr_code', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'source_': 'source',
    'amendedAt': 'amended_at',
    'qrCode': 'qr_code',
  },
);

Map<String, dynamic> _$AcademicRegisterLearnerToJson(
  AcademicRegisterLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'state': instance.state,
  'revision': instance.revision,
  'source': instance.source_,
  'amended_at': instance.amendedAt,
  'qr_code': instance.qrCode,
};
