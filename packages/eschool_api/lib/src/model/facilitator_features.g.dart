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
        );
        return val;
      },
      fieldKeyMap: const {
        'housePoints': 'house_points',
        'riskInterventions': 'risk_interventions',
        'supportPlans': 'support_plans',
        'learnerNotes': 'learner_notes',
      },
    );

Map<String, dynamic> _$FacilitatorFeaturesToJson(
  FacilitatorFeatures instance,
) => <String, dynamic>{
  'house_points': ?instance.housePoints,
  'awards': ?instance.awards,
  'discipline': ?instance.discipline,
  'risk_interventions': ?instance.riskInterventions,
  'groups': instance.groups,
  'messages': instance.messages,
  'counselling': instance.counselling,
  'support_plans': ?instance.supportPlans,
  'learner_notes': ?instance.learnerNotes,
};
