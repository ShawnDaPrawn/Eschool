//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_features.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorFeatures {
  /// Returns a new [FacilitatorFeatures] instance.
  FacilitatorFeatures({
    this.schoolAssistance,

    this.teachingAssistant,

    this.testInsights,

    this.myGrade,

    this.liveUpdates,

    this.gallery,

    this.dailyPractice,

    this.consultations,

    this.privateNotices,

    this.housePoints,

    this.awards,

    this.discipline,

    this.riskInterventions,

    required this.groups,

    required this.messages,

    required this.counselling,

    this.supportPlans,

    this.learnerNotes,

    this.cards,

    this.familyForms,

    this.examCoach,
  });

  /// Optional current school-office support capability; exact thread assignment is checked before every private body read.
  @JsonKey(name: r'school_assistance', required: false, includeIfNull: false)
  final bool? schoolAssistance;

  /// Optional fresh allocated-Teacher draft capability; absence means unavailable.
  @JsonKey(name: r'teaching_assistant', required: false, includeIfNull: false)
  final bool? teachingAssistant;

  /// Optional fresh allocated-Teacher task-insight capability; no generation authority is granted.
  @JsonKey(name: r'test_insights', required: false, includeIfNull: false)
  final bool? testInsights;

  /// A current dated grade-head working hub is available.
  @JsonKey(name: r'my_grade', required: false, includeIfNull: false)
  final bool? myGrade;

  /// Optional fresh live-session capability, emitted only when the live transport is implemented and authorized.
  @JsonKey(name: r'live_updates', required: false, includeIfNull: false)
  final bool? liveUpdates;

  /// Optional current gallery group capability. Every group, file and tag requires fresh source authority.
  @JsonKey(name: r'gallery', required: false, includeIfNull: false)
  final bool? gallery;

  /// Optional fresh allocated-Teacher practice authoring capability. Every group/source is authorized again.
  @JsonKey(name: r'daily_practice', required: false, includeIfNull: false)
  final bool? dailyPractice;

  /// Optional current-school consultation capability; every ballot still requires fresh audience authority.
  @JsonKey(name: r'consultations', required: false, includeIfNull: false)
  final bool? consultations;

  /// Optional fresh dedicated staff inbox capability; absence means unavailable. Every notice still requires its source grant.
  @JsonKey(name: r'private_notices', required: false, includeIfNull: false)
  final bool? privateNotices;

  /// Optional fresh capability; absence preserves older responses. Record-specific authority remains required.
  @JsonKey(name: r'house_points', required: false, includeIfNull: false)
  final bool? housePoints;

  /// Optional fresh capability; absence preserves older responses. Record-specific authority remains required.
  @JsonKey(name: r'awards', required: false, includeIfNull: false)
  final bool? awards;

  /// Optional capability; absence preserves older facilitator session responses. Fresh record-specific authority remains required.
  @JsonKey(name: r'discipline', required: false, includeIfNull: false)
  final bool? discipline;

  /// Fresh original-kind authority for private risk interventions; absence means unavailable.
  @JsonKey(name: r'risk_interventions', required: false, includeIfNull: false)
  final bool? riskInterventions;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final bool groups;

  @JsonKey(name: r'messages', required: true, includeIfNull: false)
  final bool messages;

  @JsonKey(name: r'counselling', required: true, includeIfNull: false)
  final bool counselling;

  /// Optional capability; absence preserves compatibility with older facilitator sessions. Fresh record-specific authority is still required.
  @JsonKey(name: r'support_plans', required: false, includeIfNull: false)
  final bool? supportPlans;

  /// Optional capability; absence preserves compatibility with older facilitator sessions. Fresh record-specific authority is still required.
  @JsonKey(name: r'learner_notes', required: false, includeIfNull: false)
  final bool? learnerNotes;

  /// Fresh card module/account access; does not grant issuance or device management rights.
  @JsonKey(name: r'cards', required: false, includeIfNull: false)
  final bool? cards;

  /// Fresh staff family-form capability; does not grant guardian or clinical access.
  @JsonKey(name: r'family_forms', required: false, includeIfNull: false)
  final bool? familyForms;

  /// Optional current exam-coach entry capability.
  @JsonKey(name: r'exam_coach', required: false, includeIfNull: false)
  final bool? examCoach;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorFeatures &&
          other.schoolAssistance == schoolAssistance &&
          other.teachingAssistant == teachingAssistant &&
          other.testInsights == testInsights &&
          other.myGrade == myGrade &&
          other.liveUpdates == liveUpdates &&
          other.gallery == gallery &&
          other.dailyPractice == dailyPractice &&
          other.consultations == consultations &&
          other.privateNotices == privateNotices &&
          other.housePoints == housePoints &&
          other.awards == awards &&
          other.discipline == discipline &&
          other.riskInterventions == riskInterventions &&
          other.groups == groups &&
          other.messages == messages &&
          other.counselling == counselling &&
          other.supportPlans == supportPlans &&
          other.learnerNotes == learnerNotes &&
          other.cards == cards &&
          other.familyForms == familyForms &&
          other.examCoach == examCoach;

  @override
  int get hashCode =>
      schoolAssistance.hashCode +
      teachingAssistant.hashCode +
      testInsights.hashCode +
      myGrade.hashCode +
      liveUpdates.hashCode +
      gallery.hashCode +
      dailyPractice.hashCode +
      consultations.hashCode +
      privateNotices.hashCode +
      housePoints.hashCode +
      awards.hashCode +
      discipline.hashCode +
      riskInterventions.hashCode +
      groups.hashCode +
      messages.hashCode +
      counselling.hashCode +
      supportPlans.hashCode +
      learnerNotes.hashCode +
      cards.hashCode +
      familyForms.hashCode +
      examCoach.hashCode;

  factory FacilitatorFeatures.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorFeaturesFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorFeaturesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
