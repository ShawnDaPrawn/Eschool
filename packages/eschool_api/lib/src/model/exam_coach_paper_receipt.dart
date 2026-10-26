//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_paper_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPaperReceipt {
  /// Returns a new [ExamCoachPaperReceipt] instance.
  ExamCoachPaperReceipt({
    required this.id,

    required this.versionId,

    required this.revision,

    required this.state,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'version_id', required: true, includeIfNull: true)
  final String? versionId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachPaperReceiptStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPaperReceiptStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPaperReceipt &&
          other.id == id &&
          other.versionId == versionId &&
          other.revision == revision &&
          other.state == state;

  @override
  int get hashCode =>
      id.hashCode +
      (versionId == null ? 0 : versionId.hashCode) +
      revision.hashCode +
      state.hashCode;

  factory ExamCoachPaperReceipt.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPaperReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPaperReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachPaperReceiptStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPaperReceiptStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
