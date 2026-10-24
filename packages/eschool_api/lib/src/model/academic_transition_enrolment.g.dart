// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_enrolment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionEnrolment _$AcademicTransitionEnrolmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionEnrolment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'session_year_id',
        'class_section_id',
        'starts_on',
        'ends_on',
        'cancelled_at',
      ],
    );
    final val = AcademicTransitionEnrolment(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String?),
      cancelledAt: $checkedConvert(
        'cancelled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'classSectionId': 'class_section_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'cancelledAt': 'cancelled_at',
  },
);

Map<String, dynamic> _$AcademicTransitionEnrolmentToJson(
  AcademicTransitionEnrolment instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year_id': instance.sessionYearId,
  'class_section_id': instance.classSectionId,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'cancelled_at': instance.cancelledAt?.toIso8601String(),
};
