//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_activity.dart';
import 'package:eschool_api/src/model/learner_passport_service.dart';
import 'package:eschool_api/src/model/learner_passport_award.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_school.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportSchool {
  /// Returns a new [LearnerPassportSchool] instance.
  LearnerPassportSchool({
    required this.name,

    required this.current,

    required this.attendedFrom,

    required this.attendedTo,

    required this.awards,

    required this.activities,

    required this.service,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  /// The school the caller is signed in at.
  @JsonKey(name: r'current', required: true, includeIfNull: false)
  final bool current;

  @JsonKey(name: r'attended_from', required: true, includeIfNull: true)
  final DateTime? attendedFrom;

  /// Null while the learner still attends.
  @JsonKey(name: r'attended_to', required: true, includeIfNull: true)
  final DateTime? attendedTo;

  @JsonKey(name: r'awards', required: true, includeIfNull: false)
  final List<LearnerPassportAward> awards;

  @JsonKey(name: r'activities', required: true, includeIfNull: false)
  final List<LearnerPassportActivity> activities;

  @JsonKey(name: r'service', required: true, includeIfNull: true)
  final LearnerPassportService? service;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportSchool &&
          other.name == name &&
          other.current == current &&
          other.attendedFrom == attendedFrom &&
          other.attendedTo == attendedTo &&
          other.awards == awards &&
          other.activities == activities &&
          other.service == service;

  @override
  int get hashCode =>
      name.hashCode +
      current.hashCode +
      (attendedFrom == null ? 0 : attendedFrom.hashCode) +
      (attendedTo == null ? 0 : attendedTo.hashCode) +
      awards.hashCode +
      activities.hashCode +
      (service == null ? 0 : service.hashCode);

  factory LearnerPassportSchool.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportSchoolFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportSchoolToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
