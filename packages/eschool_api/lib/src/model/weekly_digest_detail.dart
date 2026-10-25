//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_evidence.dart';
import 'package:eschool_api/src/model/weekly_digest_home_action.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestDetail {
  /// Returns a new [WeeklyDigestDetail] instance.
  WeeklyDigestDetail({
    required this.id,

    required this.learnerId,

    required this.language,

    required this.weekStart,

    required this.weekEnd,

    required this.templateVersion,

    required this.title,

    required this.summary,

    required this.evidence,

    required this.homeAction,

    required this.expiresAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'week_start', required: true, includeIfNull: false)
  final String weekStart;

  @JsonKey(name: r'week_end', required: true, includeIfNull: false)
  final String weekEnd;

  // minimum: 1
  @JsonKey(name: r'template_version', required: true, includeIfNull: false)
  final int templateVersion;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final String summary;

  @JsonKey(name: r'evidence', required: true, includeIfNull: false)
  final WeeklyDigestEvidence evidence;

  @JsonKey(name: r'home_action', required: true, includeIfNull: false)
  final WeeklyDigestHomeAction homeAction;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestDetail &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.language == language &&
          other.weekStart == weekStart &&
          other.weekEnd == weekEnd &&
          other.templateVersion == templateVersion &&
          other.title == title &&
          other.summary == summary &&
          other.evidence == evidence &&
          other.homeAction == homeAction &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      language.hashCode +
      weekStart.hashCode +
      weekEnd.hashCode +
      templateVersion.hashCode +
      title.hashCode +
      summary.hashCode +
      evidence.hashCode +
      homeAction.hashCode +
      expiresAt.hashCode;

  factory WeeklyDigestDetail.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestDetailFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
