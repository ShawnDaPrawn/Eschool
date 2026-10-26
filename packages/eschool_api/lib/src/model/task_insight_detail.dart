//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/task_insight_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'task_insight_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TaskInsightDetail {
  /// Returns a new [TaskInsightDetail] instance.
  TaskInsightDetail({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.fingerprint,

    required this.computedAt,

    required this.summary,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'computed_at', required: true, includeIfNull: false)
  final DateTime computedAt;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final TaskInsightSummary summary;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TaskInsightDetail &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.fingerprint == fingerprint &&
          other.computedAt == computedAt &&
          other.summary == summary;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      fingerprint.hashCode +
      computedAt.hashCode +
      summary.hashCode;

  factory TaskInsightDetail.fromJson(Map<String, dynamic> json) =>
      _$TaskInsightDetailFromJson(json);

  Map<String, dynamic> toJson() => _$TaskInsightDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
