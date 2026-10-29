//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_homework.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryHomework {
  /// Returns a new [DiaryHomework] instance.
  DiaryHomework({
    required this.submitted,

    required this.status,

    required this.points,

    required this.total,

    required this.overdue,
  });

  @JsonKey(name: r'submitted', required: true, includeIfNull: false)
  final bool submitted;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: true,
    unknownEnumValue: DiaryHomeworkStatusEnum.unknownDefaultOpenApi,
  )
  final DiaryHomeworkStatusEnum? status;

  @JsonKey(name: r'points', required: true, includeIfNull: true)
  final int? points;

  @JsonKey(name: r'total', required: true, includeIfNull: true)
  final int? total;

  @JsonKey(name: r'overdue', required: true, includeIfNull: false)
  final bool overdue;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryHomework &&
          other.submitted == submitted &&
          other.status == status &&
          other.points == points &&
          other.total == total &&
          other.overdue == overdue;

  @override
  int get hashCode =>
      submitted.hashCode +
      (status == null ? 0 : status.hashCode) +
      (points == null ? 0 : points.hashCode) +
      (total == null ? 0 : total.hashCode) +
      overdue.hashCode;

  factory DiaryHomework.fromJson(Map<String, dynamic> json) =>
      _$DiaryHomeworkFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryHomeworkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DiaryHomeworkStatusEnum {
  @JsonValue(r'in_review')
  inReview(r'in_review'),
  @JsonValue(r'accepted')
  accepted(r'accepted'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'resubmitted')
  resubmitted(r'resubmitted'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiaryHomeworkStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
