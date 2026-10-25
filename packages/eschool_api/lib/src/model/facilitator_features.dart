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
  });

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorFeatures &&
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
          other.learnerNotes == learnerNotes;

  @override
  int get hashCode =>
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
      learnerNotes.hashCode;

  factory FacilitatorFeatures.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorFeaturesFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorFeaturesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
