// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorConfiguration _$TutorConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TutorConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'learner_id',
        'session_year_id',
        'groups',
        'inference_enabled',
        'photo_requires_approved_crop',
        'feedback_is_provisional',
      ],
    );
    final val = TutorConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map((e) => TutorGroupChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      inferenceEnabled: $checkedConvert('inference_enabled', (v) => v as bool),
      photoRequiresApprovedCrop: $checkedConvert(
        'photo_requires_approved_crop',
        (v) => v as bool,
      ),
      feedbackIsProvisional: $checkedConvert(
        'feedback_is_provisional',
        (v) => v as bool,
      ),
      checkpointsEnabled: $checkedConvert(
        'checkpoints_enabled',
        (v) => v as bool?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'inferenceEnabled': 'inference_enabled',
    'photoRequiresApprovedCrop': 'photo_requires_approved_crop',
    'feedbackIsProvisional': 'feedback_is_provisional',
    'checkpointsEnabled': 'checkpoints_enabled',
  },
);

Map<String, dynamic> _$TutorConfigurationToJson(TutorConfiguration instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'session_year_id': instance.sessionYearId,
      'groups': instance.groups.map((e) => e.toJson()).toList(),
      'inference_enabled': instance.inferenceEnabled,
      'photo_requires_approved_crop': instance.photoRequiresApprovedCrop,
      'feedback_is_provisional': instance.feedbackIsProvisional,
      'checkpoints_enabled': ?instance.checkpointsEnabled,
    };
