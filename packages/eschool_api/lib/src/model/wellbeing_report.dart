//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/incident_photo.dart';
import 'package:eschool_api/src/model/wellbeing_report_thread.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReport {
  /// Returns a new [WellbeingReport] instance.
  WellbeingReport({
    required this.id,

    required this.categoryId,

    required this.anonymous,

    required this.reporterUserId,

    required this.learnerId,

    required this.body,

    required this.urgency,

    required this.status,

    required this.identifiedAt,

    required this.revision,

    required this.createdAt,

    required this.threads,

    required this.categoryName,

    required this.reporterName,

    required this.learnerName,

    this.hasOlder,

    this.nextBefore,

    this.photos,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  // minimum: 1
  @JsonKey(name: r'reporter_user_id', required: true, includeIfNull: true)
  final int? reporterUserId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(
    name: r'urgency',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportUrgencyEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportUrgencyEnum urgency;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportStatusEnum status;

  @JsonKey(name: r'identified_at', required: true, includeIfNull: true)
  final DateTime? identifiedAt;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'threads', required: true, includeIfNull: false)
  final List<WellbeingReportThread> threads;

  @JsonKey(name: r'category_name', required: true, includeIfNull: false)
  final String categoryName;

  @JsonKey(name: r'reporter_name', required: true, includeIfNull: true)
  final String? reporterName;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: true)
  final String? learnerName;

  @JsonKey(name: r'has_older', required: false, includeIfNull: false)
  final bool? hasOlder;

  // minimum: 1
  @JsonKey(name: r'next_before', required: false, includeIfNull: false)
  final int? nextBefore;

  @JsonKey(name: r'photos', required: false, includeIfNull: false)
  final List<IncidentPhoto>? photos;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReport &&
          other.id == id &&
          other.categoryId == categoryId &&
          other.anonymous == anonymous &&
          other.reporterUserId == reporterUserId &&
          other.learnerId == learnerId &&
          other.body == body &&
          other.urgency == urgency &&
          other.status == status &&
          other.identifiedAt == identifiedAt &&
          other.revision == revision &&
          other.createdAt == createdAt &&
          other.threads == threads &&
          other.categoryName == categoryName &&
          other.reporterName == reporterName &&
          other.learnerName == learnerName &&
          other.hasOlder == hasOlder &&
          other.nextBefore == nextBefore &&
          other.photos == photos;

  @override
  int get hashCode =>
      id.hashCode +
      categoryId.hashCode +
      anonymous.hashCode +
      (reporterUserId == null ? 0 : reporterUserId.hashCode) +
      (learnerId == null ? 0 : learnerId.hashCode) +
      body.hashCode +
      urgency.hashCode +
      status.hashCode +
      (identifiedAt == null ? 0 : identifiedAt.hashCode) +
      revision.hashCode +
      createdAt.hashCode +
      threads.hashCode +
      categoryName.hashCode +
      (reporterName == null ? 0 : reporterName.hashCode) +
      (learnerName == null ? 0 : learnerName.hashCode) +
      hasOlder.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode) +
      photos.hashCode;

  factory WellbeingReport.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingReportUrgencyEnum {
  @JsonValue(r'routine')
  routine(r'routine'),
  @JsonValue(r'urgent')
  urgent(r'urgent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportUrgencyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WellbeingReportStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'triaged')
  triaged(r'triaged'),
  @JsonValue(r'resolved')
  resolved(r'resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
