//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_mark.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestMark {
  /// Returns a new [WeeklyDigestMark] instance.
  WeeklyDigestMark({
    required this.releaseId,

    required this.subject,

    required this.percentageBp,

    required this.provisional,
  });

  // minimum: 1
  @JsonKey(name: r'release_id', required: true, includeIfNull: false)
  final int releaseId;

  @JsonKey(name: r'subject', required: true, includeIfNull: false)
  final String subject;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestMark &&
          other.releaseId == releaseId &&
          other.subject == subject &&
          other.percentageBp == percentageBp &&
          other.provisional == provisional;

  @override
  int get hashCode =>
      releaseId.hashCode +
      subject.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      provisional.hashCode;

  factory WeeklyDigestMark.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestMarkFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestMarkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
