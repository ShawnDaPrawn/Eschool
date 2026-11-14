// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_academic_competition_feedback_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerAcademicCompetitionFeedbackInner
_$LearnerAcademicCompetitionFeedbackInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearnerAcademicCompetitionFeedbackInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['text', 'shared_on']);
      final val = LearnerAcademicCompetitionFeedbackInner(
        text: $checkedConvert('text', (v) => v as String),
        sharedOn: $checkedConvert('shared_on', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'sharedOn': 'shared_on'});

Map<String, dynamic> _$LearnerAcademicCompetitionFeedbackInnerToJson(
  LearnerAcademicCompetitionFeedbackInner instance,
) => <String, dynamic>{'text': instance.text, 'shared_on': instance.sharedOn};
