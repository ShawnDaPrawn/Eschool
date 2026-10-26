// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_features.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorFeatures _$FacilitatorFeaturesFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FacilitatorFeatures',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['groups', 'messages', 'counselling'],
        );
        final val = FacilitatorFeatures(
          teachingAssistant: $checkedConvert(
            'teaching_assistant',
            (v) => v as bool?,
          ),
          testInsights: $checkedConvert('test_insights', (v) => v as bool?),
          myGrade: $checkedConvert('my_grade', (v) => v as bool?),
          liveUpdates: $checkedConvert('live_updates', (v) => v as bool?),
          gallery: $checkedConvert('gallery', (v) => v as bool?),
          dailyPractice: $checkedConvert('daily_practice', (v) => v as bool?),
          consultations: $checkedConvert('consultations', (v) => v as bool?),
          privateNotices: $checkedConvert('private_notices', (v) => v as bool?),
          housePoints: $checkedConvert('house_points', (v) => v as bool?),
          awards: $checkedConvert('awards', (v) => v as bool?),
          discipline: $checkedConvert('discipline', (v) => v as bool?),
          riskInterventions: $checkedConvert(
            'risk_interventions',
            (v) => v as bool?,
          ),
          groups: $checkedConvert('groups', (v) => v as bool),
          messages: $checkedConvert('messages', (v) => v as bool),
          counselling: $checkedConvert('counselling', (v) => v as bool),
          supportPlans: $checkedConvert('support_plans', (v) => v as bool?),
          learnerNotes: $checkedConvert('learner_notes', (v) => v as bool?),
          cards: $checkedConvert('cards', (v) => v as bool?),
          familyForms: $checkedConvert('family_forms', (v) => v as bool?),
          examCoach: $checkedConvert('exam_coach', (v) => v as bool?),
        );
        return val;
      },
      fieldKeyMap: const {
        'teachingAssistant': 'teaching_assistant',
        'testInsights': 'test_insights',
        'myGrade': 'my_grade',
        'liveUpdates': 'live_updates',
        'dailyPractice': 'daily_practice',
        'privateNotices': 'private_notices',
        'housePoints': 'house_points',
        'riskInterventions': 'risk_interventions',
        'supportPlans': 'support_plans',
        'learnerNotes': 'learner_notes',
        'familyForms': 'family_forms',
        'examCoach': 'exam_coach',
      },
    );

Map<String, dynamic> _$FacilitatorFeaturesToJson(
  FacilitatorFeatures instance,
) => <String, dynamic>{
  'teaching_assistant': ?instance.teachingAssistant,
  'test_insights': ?instance.testInsights,
  'my_grade': ?instance.myGrade,
  'live_updates': ?instance.liveUpdates,
  'gallery': ?instance.gallery,
  'daily_practice': ?instance.dailyPractice,
  'consultations': ?instance.consultations,
  'private_notices': ?instance.privateNotices,
  'house_points': ?instance.housePoints,
  'awards': ?instance.awards,
  'discipline': ?instance.discipline,
  'risk_interventions': ?instance.riskInterventions,
  'groups': instance.groups,
  'messages': instance.messages,
  'counselling': instance.counselling,
  'support_plans': ?instance.supportPlans,
  'learner_notes': ?instance.learnerNotes,
  'cards': ?instance.cards,
  'family_forms': ?instance.familyForms,
  'exam_coach': ?instance.examCoach,
};
