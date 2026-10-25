//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_case_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportCaseSummary {
  /// Returns a new [SupportCaseSummary] instance.
  SupportCaseSummary({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.revision,

    required this.state,

    required this.reviewDueOn,
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
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportCaseSummaryStateEnum.unknownDefaultOpenApi,
  )
  final SupportCaseSummaryStateEnum state;

  @JsonKey(name: r'review_due_on', required: true, includeIfNull: true)
  final String? reviewDueOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportCaseSummary &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.revision == revision &&
          other.state == state &&
          other.reviewDueOn == reviewDueOn;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      revision.hashCode +
      state.hashCode +
      (reviewDueOn == null ? 0 : reviewDueOn.hashCode);

  factory SupportCaseSummary.fromJson(Map<String, dynamic> json) =>
      _$SupportCaseSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$SupportCaseSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportCaseSummaryStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportCaseSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
