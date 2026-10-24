// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_row_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterRowWrite _$AcademicRegisterRowWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterRowWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'state',
        'source',
        'expected_revision',
      ],
    );
    final val = AcademicRegisterRowWrite(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      state: $checkedConvert('state', (v) => v as String),
      source_: $checkedConvert('source', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      qrToken: $checkedConvert('qr_token', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'source_': 'source',
    'expectedRevision': 'expected_revision',
    'qrToken': 'qr_token',
  },
);

Map<String, dynamic> _$AcademicRegisterRowWriteToJson(
  AcademicRegisterRowWrite instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'state': instance.state,
  'source': instance.source_,
  'expected_revision': instance.expectedRevision,
  'qr_token': ?instance.qrToken,
};
