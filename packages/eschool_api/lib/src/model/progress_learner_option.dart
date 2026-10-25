//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_learner_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressLearnerOption {
  /// Returns a new [ProgressLearnerOption] instance.
  ProgressLearnerOption({required this.learnerId, required this.displayName});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'display_name', required: true, includeIfNull: false)
  final String displayName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressLearnerOption &&
          other.learnerId == learnerId &&
          other.displayName == displayName;

  @override
  int get hashCode => learnerId.hashCode + displayName.hashCode;

  factory ProgressLearnerOption.fromJson(Map<String, dynamic> json) =>
      _$ProgressLearnerOptionFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressLearnerOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
