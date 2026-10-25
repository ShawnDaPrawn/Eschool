//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_roster_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryRosterLearner {
  /// Returns a new [MasteryRosterLearner] instance.
  MasteryRosterLearner({required this.learnerId, required this.displayName});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'display_name', required: true, includeIfNull: false)
  final String displayName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryRosterLearner &&
          other.learnerId == learnerId &&
          other.displayName == displayName;

  @override
  int get hashCode => learnerId.hashCode + displayName.hashCode;

  factory MasteryRosterLearner.fromJson(Map<String, dynamic> json) =>
      _$MasteryRosterLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryRosterLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
