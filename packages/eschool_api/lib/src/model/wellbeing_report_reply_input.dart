//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report_reply_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReportReplyInput {
  /// Returns a new [WellbeingReportReplyInput] instance.
  WellbeingReportReplyInput({
    required this.operationId,

    required this.revision,

    required this.body,

    required this.internal,

    required this.status,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'internal', required: true, includeIfNull: false)
  final bool internal;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportReplyInputStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportReplyInputStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReportReplyInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.body == body &&
          other.internal == internal &&
          other.status == status;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      body.hashCode +
      internal.hashCode +
      status.hashCode;

  factory WellbeingReportReplyInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportReplyInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportReplyInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingReportReplyInputStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'triaged')
  triaged(r'triaged'),
  @JsonValue(r'resolved')
  resolved(r'resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportReplyInputStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
