//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReportSummary {
  /// Returns a new [WellbeingReportSummary] instance.
  WellbeingReportSummary({
    required this.id,

    required this.categoryId,

    required this.anonymous,

    required this.urgency,

    required this.status,

    required this.revision,

    required this.createdAt,

    required this.categoryName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(
    name: r'urgency',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportSummaryUrgencyEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportSummaryUrgencyEnum urgency;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportSummaryStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportSummaryStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'category_name', required: true, includeIfNull: false)
  final String categoryName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReportSummary &&
          other.id == id &&
          other.categoryId == categoryId &&
          other.anonymous == anonymous &&
          other.urgency == urgency &&
          other.status == status &&
          other.revision == revision &&
          other.createdAt == createdAt &&
          other.categoryName == categoryName;

  @override
  int get hashCode =>
      id.hashCode +
      categoryId.hashCode +
      anonymous.hashCode +
      urgency.hashCode +
      status.hashCode +
      revision.hashCode +
      createdAt.hashCode +
      categoryName.hashCode;

  factory WellbeingReportSummary.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingReportSummaryUrgencyEnum {
  @JsonValue(r'routine')
  routine(r'routine'),
  @JsonValue(r'urgent')
  urgent(r'urgent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportSummaryUrgencyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WellbeingReportSummaryStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'triaged')
  triaged(r'triaged'),
  @JsonValue(r'resolved')
  resolved(r'resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportSummaryStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
