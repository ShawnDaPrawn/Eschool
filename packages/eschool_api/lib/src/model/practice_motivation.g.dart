// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_motivation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMotivation _$PracticeMotivationFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeMotivation',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'learner_id',
            'can_set_goal',
            'streak',
            'goal',
            'secure_topics',
          ],
        );
        final val = PracticeMotivation(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          canSetGoal: $checkedConvert('can_set_goal', (v) => v as bool),
          streak: $checkedConvert(
            'streak',
            (v) => PracticeMotivationStreak.fromJson(v as Map<String, dynamic>),
          ),
          goal: $checkedConvert(
            'goal',
            (v) => PracticeMotivationGoal.fromJson(v as Map<String, dynamic>),
          ),
          secureTopics: $checkedConvert(
            'secure_topics',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => PracticeMotivationSecureTopicsInner.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'canSetGoal': 'can_set_goal',
        'secureTopics': 'secure_topics',
      },
    );

Map<String, dynamic> _$PracticeMotivationToJson(PracticeMotivation instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'can_set_goal': instance.canSetGoal,
      'streak': instance.streak.toJson(),
      'goal': instance.goal.toJson(),
      'secure_topics': instance.secureTopics.map((e) => e.toJson()).toList(),
    };
