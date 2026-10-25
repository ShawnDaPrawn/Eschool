//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_case_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCase {
  /// Returns a new [WellbeingCase] instance.
  WellbeingCase({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.incidentReportId,

    required this.status,

    required this.openedAt,

    required this.revision,

    required this.entries,

    required this.hasOlder,

    required this.nextBefore,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'incident_report_id', required: true, includeIfNull: true)
  final int? incidentReportId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseStatusEnum status;

  @JsonKey(name: r'opened_at', required: true, includeIfNull: false)
  final DateTime openedAt;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<WellbeingCaseEntry> entries;

  @JsonKey(name: r'has_older', required: true, includeIfNull: false)
  final bool hasOlder;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCase &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.incidentReportId == incidentReportId &&
          other.status == status &&
          other.openedAt == openedAt &&
          other.revision == revision &&
          other.entries == entries &&
          other.hasOlder == hasOlder &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      (incidentReportId == null ? 0 : incidentReportId.hashCode) +
      status.hashCode +
      openedAt.hashCode +
      revision.hashCode +
      entries.hashCode +
      hasOlder.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode);

  factory WellbeingCase.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
