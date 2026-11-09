//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_motivation_secure_topics_inner.dart';
import 'package:eschool_api/src/model/practice_motivation_streak.dart';
import 'package:eschool_api/src/model/practice_motivation_goal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_motivation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMotivation {
  /// Returns a new [PracticeMotivation] instance.
  PracticeMotivation({
    required this.learnerId,

    required this.canSetGoal,

    required this.streak,

    required this.goal,

    required this.secureTopics,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'can_set_goal', required: true, includeIfNull: false)
  final bool canSetGoal;

  @JsonKey(name: r'streak', required: true, includeIfNull: false)
  final PracticeMotivationStreak streak;

  @JsonKey(name: r'goal', required: true, includeIfNull: false)
  final PracticeMotivationGoal goal;

  @JsonKey(name: r'secure_topics', required: true, includeIfNull: false)
  final List<PracticeMotivationSecureTopicsInner> secureTopics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMotivation &&
          other.learnerId == learnerId &&
          other.canSetGoal == canSetGoal &&
          other.streak == streak &&
          other.goal == goal &&
          other.secureTopics == secureTopics;

  @override
  int get hashCode =>
      learnerId.hashCode +
      canSetGoal.hashCode +
      streak.hashCode +
      goal.hashCode +
      secureTopics.hashCode;

  factory PracticeMotivation.fromJson(Map<String, dynamic> json) =>
      _$PracticeMotivationFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMotivationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
