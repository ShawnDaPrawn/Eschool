// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'enrolment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Enrolment _$EnrolmentFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Enrolment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'class_section_id',
        'session_year_id',
        'starts_on',
        'ends_on',
        'reason',
        'legacy_dates',
        'previous_enrolment_id',
      ],
    );
    final val = Enrolment(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => DateTime.parse(v as String),
      ),
      endsOn: $checkedConvert(
        'ends_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
      legacyDates: $checkedConvert('legacy_dates', (v) => v as bool),
      previousEnrolmentId: $checkedConvert(
        'previous_enrolment_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'classSectionId': 'class_section_id',
    'sessionYearId': 'session_year_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'legacyDates': 'legacy_dates',
    'previousEnrolmentId': 'previous_enrolment_id',
  },
);

Map<String, dynamic> _$EnrolmentToJson(Enrolment instance) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'class_section_id': instance.classSectionId,
  'session_year_id': instance.sessionYearId,
  'starts_on': instance.startsOn.toIso8601String(),
  'ends_on': instance.endsOn?.toIso8601String(),
  'reason': instance.reason,
  'legacy_dates': instance.legacyDates,
  'previous_enrolment_id': instance.previousEnrolmentId,
};
