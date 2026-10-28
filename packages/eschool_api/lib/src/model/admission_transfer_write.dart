//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_transfer_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTransferWrite {
  /// Returns a new [AdmissionTransferWrite] instance.
  AdmissionTransferWrite({
    required this.operationId,

    required this.revision,

    required this.receiveCode,

    required this.recipientId,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'receive_code', required: true, includeIfNull: false)
  final String receiveCode;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'recipient_id', required: true, includeIfNull: false)
  final int recipientId;

  /// Must be true to confirm this original ticket action.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTransferWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.receiveCode == receiveCode &&
          other.recipientId == recipientId &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      receiveCode.hashCode +
      recipientId.hashCode +
      confirmed.hashCode;

  factory AdmissionTransferWrite.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTransferWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTransferWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
