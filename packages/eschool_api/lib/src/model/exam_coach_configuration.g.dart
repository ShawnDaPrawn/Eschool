// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachConfiguration _$ExamCoachConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'reader_id',
        'learner_id',
        'staff_groups',
        'learner_groups',
        'writable',
      ],
    );
    final val = ExamCoachConfiguration(
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      staffGroups: $checkedConvert(
        'staff_groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ExamCoachGroupChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      learnerGroups: $checkedConvert(
        'learner_groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ExamCoachGroupChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      writable: $checkedConvert('writable', (v) => v as bool),
      photoFeedbackEnabled: $checkedConvert(
        'photo_feedback_enabled',
        (v) => v as bool?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
    'staffGroups': 'staff_groups',
    'learnerGroups': 'learner_groups',
    'photoFeedbackEnabled': 'photo_feedback_enabled',
  },
);

Map<String, dynamic> _$ExamCoachConfigurationToJson(
  ExamCoachConfiguration instance,
) => <String, dynamic>{
  'reader_id': instance.readerId,
  'learner_id': instance.learnerId,
  'staff_groups': instance.staffGroups.map((e) => e.toJson()).toList(),
  'learner_groups': instance.learnerGroups.map((e) => e.toJson()).toList(),
  'writable': instance.writable,
  'photo_feedback_enabled': ?instance.photoFeedbackEnabled,
};
