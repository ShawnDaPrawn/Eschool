//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_write_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportWriteReceipt {
  /// Returns a new [SupportWriteReceipt] instance.
  SupportWriteReceipt({
    required this.id,

    required this.revision,

    required this.kind,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportWriteReceiptKindEnum.unknownDefaultOpenApi,
  )
  final SupportWriteReceiptKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportWriteReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.kind == kind;

  @override
  int get hashCode => id.hashCode + revision.hashCode + kind.hashCode;

  factory SupportWriteReceipt.fromJson(Map<String, dynamic> json) =>
      _$SupportWriteReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$SupportWriteReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportWriteReceiptKindEnum {
  @JsonValue(r'assignment')
  assignment(r'assignment'),
  @JsonValue(r'plan')
  plan(r'plan'),
  @JsonValue(r'review')
  review(r'review'),
  @JsonValue(r'disclosure')
  disclosure(r'disclosure'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportWriteReceiptKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
