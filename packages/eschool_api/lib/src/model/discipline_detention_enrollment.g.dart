// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_enrollment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionEnrollment _$DisciplineDetentionEnrollmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionEnrollment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_id',
        'entry_id',
        'session_revision',
        'entry_revision',
      ],
    );
    final val = DisciplineDetentionEnrollment(
      sessionId: $checkedConvert('session_id', (v) => (v as num).toInt()),
      entryId: $checkedConvert('entry_id', (v) => (v as num).toInt()),
      sessionRevision: $checkedConvert(
        'session_revision',
        (v) => (v as num).toInt(),
      ),
      entryRevision: $checkedConvert(
        'entry_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionId': 'session_id',
    'entryId': 'entry_id',
    'sessionRevision': 'session_revision',
    'entryRevision': 'entry_revision',
  },
);

Map<String, dynamic> _$DisciplineDetentionEnrollmentToJson(
  DisciplineDetentionEnrollment instance,
) => <String, dynamic>{
  'session_id': instance.sessionId,
  'entry_id': instance.entryId,
  'session_revision': instance.sessionRevision,
  'entry_revision': instance.entryRevision,
};
