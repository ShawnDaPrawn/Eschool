//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageReceipt {
  /// Returns a new [CommunicationMessageReceipt] instance.
  CommunicationMessageReceipt({
    required this.id,

    required this.revision,

    required this.state,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationMessageReceiptStateEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageReceiptStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + revision.hashCode + state.hashCode;

  factory CommunicationMessageReceipt.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationMessageReceiptStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'dispatching')
  dispatching(r'dispatching'),
  @JsonValue(r'sent')
  sent(r'sent'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'blocked')
  blocked(r'blocked'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageReceiptStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
