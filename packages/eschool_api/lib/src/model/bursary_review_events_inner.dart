//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_review_events_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryReviewEventsInner {
  /// Returns a new [BursaryReviewEventsInner] instance.
  BursaryReviewEventsInner({
    required this.action,

    required this.actorId,

    required this.actorName,

    required this.reasons,

    required this.awardKind,

    required this.awardValue,

    required this.at,
  });

  @JsonKey(name: r'action', required: true, includeIfNull: false)
  final String action;

  // minimum: 1
  @JsonKey(name: r'actor_id', required: true, includeIfNull: false)
  final int actorId;

  @JsonKey(name: r'actor_name', required: true, includeIfNull: false)
  final String actorName;

  @JsonKey(name: r'reasons', required: true, includeIfNull: true)
  final String? reasons;

  @JsonKey(name: r'award_kind', required: true, includeIfNull: true)
  final String? awardKind;

  @JsonKey(name: r'award_value', required: true, includeIfNull: true)
  final int? awardValue;

  @JsonKey(name: r'at', required: true, includeIfNull: false)
  final DateTime at;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryReviewEventsInner &&
          other.action == action &&
          other.actorId == actorId &&
          other.actorName == actorName &&
          other.reasons == reasons &&
          other.awardKind == awardKind &&
          other.awardValue == awardValue &&
          other.at == at;

  @override
  int get hashCode =>
      action.hashCode +
      actorId.hashCode +
      actorName.hashCode +
      (reasons == null ? 0 : reasons.hashCode) +
      (awardKind == null ? 0 : awardKind.hashCode) +
      (awardValue == null ? 0 : awardValue.hashCode) +
      at.hashCode;

  factory BursaryReviewEventsInner.fromJson(Map<String, dynamic> json) =>
      _$BursaryReviewEventsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryReviewEventsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
