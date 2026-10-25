//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_plan_contents.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_plan_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportPlanSnapshot {
  /// Returns a new [SupportPlanSnapshot] instance.
  SupportPlanSnapshot({
    required this.startedOn,

    required this.reviewDueOn,

    required this.state,

    required this.contents,

    required this.accommodationRevision,
  });

  @JsonKey(name: r'started_on', required: true, includeIfNull: false)
  final String startedOn;

  @JsonKey(name: r'review_due_on', required: true, includeIfNull: true)
  final String? reviewDueOn;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportPlanSnapshotStateEnum.unknownDefaultOpenApi,
  )
  final SupportPlanSnapshotStateEnum state;

  @JsonKey(name: r'contents', required: true, includeIfNull: false)
  final SupportPlanContents contents;

  // minimum: 1
  @JsonKey(
    name: r'accommodation_revision',
    required: true,
    includeIfNull: false,
  )
  final int accommodationRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportPlanSnapshot &&
          other.startedOn == startedOn &&
          other.reviewDueOn == reviewDueOn &&
          other.state == state &&
          other.contents == contents &&
          other.accommodationRevision == accommodationRevision;

  @override
  int get hashCode =>
      startedOn.hashCode +
      (reviewDueOn == null ? 0 : reviewDueOn.hashCode) +
      state.hashCode +
      contents.hashCode +
      accommodationRevision.hashCode;

  factory SupportPlanSnapshot.fromJson(Map<String, dynamic> json) =>
      _$SupportPlanSnapshotFromJson(json);

  Map<String, dynamic> toJson() => _$SupportPlanSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportPlanSnapshotStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportPlanSnapshotStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
