// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_learner_enrolment_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateLearnerEnrolmentRequest _$CreateLearnerEnrolmentRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CreateLearnerEnrolmentRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'class_section_id',
        'session_year_id',
        'starts_on',
        'reason',
      ],
    );
    final val = CreateLearnerEnrolmentRequest(
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
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'classSectionId': 'class_section_id',
    'sessionYearId': 'session_year_id',
    'startsOn': 'starts_on',
  },
);

Map<String, dynamic> _$CreateLearnerEnrolmentRequestToJson(
  CreateLearnerEnrolmentRequest instance,
) => <String, dynamic>{
  'class_section_id': instance.classSectionId,
  'session_year_id': instance.sessionYearId,
  'starts_on': instance.startsOn.toIso8601String(),
  'reason': instance.reason,
};
