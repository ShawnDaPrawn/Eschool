//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_academic_competition_feedback_inner.dart';
import 'package:eschool_api/src/model/learner_academic_competition_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_academic_competition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerAcademicCompetition {
  /// Returns a new [LearnerAcademicCompetition] instance.
  LearnerAcademicCompetition({
    required this.id,

    required this.kind,

    required this.discipline,

    required this.motion,

    required this.onDate,

    required this.startsAt,

    required this.venue,

    required this.status,

    required this.competition,

    required this.teamLabel,

    required this.opponent,

    required this.stance,

    required this.result,

    required this.feedback,

    required this.awards,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerAcademicCompetitionKindEnum.unknownDefaultOpenApi,
  )
  final LearnerAcademicCompetitionKindEnum kind;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'motion', required: true, includeIfNull: false)
  final String motion;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: true)
  final String? startsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'competition', required: true, includeIfNull: true)
  final String? competition;

  @JsonKey(name: r'team_label', required: true, includeIfNull: false)
  final String teamLabel;

  @JsonKey(name: r'opponent', required: true, includeIfNull: false)
  final String opponent;

  @JsonKey(
    name: r'stance',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        LearnerAcademicCompetitionStanceEnum.unknownDefaultOpenApi,
  )
  final LearnerAcademicCompetitionStanceEnum? stance;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final LearnerAcademicCompetitionResult result;

  /// Written feedback an adjudicator chose to share with this learner. Never the AI's text by itself.
  @JsonKey(name: r'feedback', required: true, includeIfNull: false)
  final List<LearnerAcademicCompetitionFeedbackInner> feedback;

  @JsonKey(name: r'awards', required: true, includeIfNull: false)
  final List<String> awards;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerAcademicCompetition &&
          other.id == id &&
          other.kind == kind &&
          other.discipline == discipline &&
          other.motion == motion &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.venue == venue &&
          other.status == status &&
          other.competition == competition &&
          other.teamLabel == teamLabel &&
          other.opponent == opponent &&
          other.stance == stance &&
          other.result == result &&
          other.feedback == feedback &&
          other.awards == awards;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      discipline.hashCode +
      motion.hashCode +
      onDate.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      venue.hashCode +
      status.hashCode +
      (competition == null ? 0 : competition.hashCode) +
      teamLabel.hashCode +
      opponent.hashCode +
      (stance == null ? 0 : stance.hashCode) +
      result.hashCode +
      feedback.hashCode +
      awards.hashCode;

  factory LearnerAcademicCompetition.fromJson(Map<String, dynamic> json) =>
      _$LearnerAcademicCompetitionFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerAcademicCompetitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerAcademicCompetitionKindEnum {
  @JsonValue(r'debate')
  debate(r'debate'),
  @JsonValue(r'quiz')
  quiz(r'quiz'),
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerAcademicCompetitionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearnerAcademicCompetitionStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerAcademicCompetitionStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
