//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseSummary {
  /// Returns a new [WellbeingCaseSummary] instance.
  WellbeingCaseSummary({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.status,

    required this.openedAt,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseSummaryStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseSummaryStatusEnum status;

  @JsonKey(name: r'opened_at', required: true, includeIfNull: false)
  final DateTime openedAt;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseSummary &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.status == status &&
          other.openedAt == openedAt &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      status.hashCode +
      openedAt.hashCode +
      revision.hashCode;

  factory WellbeingCaseSummary.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseSummaryStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseSummaryStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
