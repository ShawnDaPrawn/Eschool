//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_write_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferWriteReceipt {
  /// Returns a new [TransferWriteReceipt] instance.
  TransferWriteReceipt({
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
    unknownEnumValue: TransferWriteReceiptKindEnum.unknownDefaultOpenApi,
  )
  final TransferWriteReceiptKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferWriteReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.kind == kind;

  @override
  int get hashCode => id.hashCode + revision.hashCode + kind.hashCode;

  factory TransferWriteReceipt.fromJson(Map<String, dynamic> json) =>
      _$TransferWriteReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$TransferWriteReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TransferWriteReceiptKindEnum {
  @JsonValue(r'package')
  package(r'package'),
  @JsonValue(r'receipt')
  receipt(r'receipt'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TransferWriteReceiptKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
