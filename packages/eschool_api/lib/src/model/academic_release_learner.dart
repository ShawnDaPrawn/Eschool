//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleaseLearner {
  /// Returns a new [AcademicReleaseLearner] instance.
  AcademicReleaseLearner({
    required this.learnerId,

    required this.snapshotId,

    required this.fingerprint,

    required this.percentageBp,

    required this.ratingLevel,

    required this.coverageBp,

    required this.complete,

    required this.ready,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'snapshot_id', required: true, includeIfNull: false)
  final int snapshotId;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  @JsonKey(name: r'complete', required: true, includeIfNull: false)
  final bool complete;

  @JsonKey(name: r'ready', required: true, includeIfNull: false)
  final bool ready;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleaseLearner &&
          other.learnerId == learnerId &&
          other.snapshotId == snapshotId &&
          other.fingerprint == fingerprint &&
          other.percentageBp == percentageBp &&
          other.ratingLevel == ratingLevel &&
          other.coverageBp == coverageBp &&
          other.complete == complete &&
          other.ready == ready;

  @override
  int get hashCode =>
      learnerId.hashCode +
      snapshotId.hashCode +
      fingerprint.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      coverageBp.hashCode +
      complete.hashCode +
      ready.hashCode;

  factory AcademicReleaseLearner.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleaseLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleaseLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
