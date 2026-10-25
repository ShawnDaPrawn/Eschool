//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_home_action.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestHomeAction {
  /// Returns a new [WeeklyDigestHomeAction] instance.
  WeeklyDigestHomeAction({
    required this.kind,

    required this.learnerId,

    required this.label,

    required this.contentVersionId,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WeeklyDigestHomeActionKindEnum.unknownDefaultOpenApi,
  )
  final WeeklyDigestHomeActionKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: true)
  final int? contentVersionId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestHomeAction &&
          other.kind == kind &&
          other.learnerId == learnerId &&
          other.label == label &&
          other.contentVersionId == contentVersionId;

  @override
  int get hashCode =>
      kind.hashCode +
      learnerId.hashCode +
      label.hashCode +
      (contentVersionId == null ? 0 : contentVersionId.hashCode);

  factory WeeklyDigestHomeAction.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestHomeActionFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestHomeActionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WeeklyDigestHomeActionKindEnum {
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'marks')
  marks(r'marks'),
  @JsonValue(r'attendance')
  attendance(r'attendance'),
  @JsonValue(r'calendar')
  calendar(r'calendar'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WeeklyDigestHomeActionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
