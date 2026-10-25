//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_session_roster_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivitySessionRosterLearner {
  /// Returns a new [ActivitySessionRosterLearner] instance.
  ActivitySessionRosterLearner({
    required this.learnerId,

    required this.learnerName,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'status', required: true, includeIfNull: true)
  final String? status;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivitySessionRosterLearner &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      learnerId.hashCode +
      learnerName.hashCode +
      (status == null ? 0 : status.hashCode) +
      revision.hashCode;

  factory ActivitySessionRosterLearner.fromJson(Map<String, dynamic> json) =>
      _$ActivitySessionRosterLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$ActivitySessionRosterLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
