//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_link_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseLinkReceipt {
  /// Returns a new [WellbeingCaseLinkReceipt] instance.
  WellbeingCaseLinkReceipt({
    required this.id,

    required this.learnerId,

    required this.incidentReportId,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'incident_report_id', required: true, includeIfNull: false)
  final int incidentReportId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseLinkReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseLinkReceiptStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseLinkReceipt &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.incidentReportId == incidentReportId &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      incidentReportId.hashCode +
      status.hashCode +
      revision.hashCode;

  factory WellbeingCaseLinkReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseLinkReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseLinkReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseLinkReceiptStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseLinkReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
