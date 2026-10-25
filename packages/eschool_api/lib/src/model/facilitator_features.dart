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
    this.riskInterventions,

    required this.groups,

    required this.messages,

    required this.counselling,

    this.supportPlans,

    this.learnerNotes,
  });

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
          other.riskInterventions == riskInterventions &&
          other.groups == groups &&
          other.messages == messages &&
          other.counselling == counselling &&
          other.supportPlans == supportPlans &&
          other.learnerNotes == learnerNotes;

  @override
  int get hashCode =>
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
