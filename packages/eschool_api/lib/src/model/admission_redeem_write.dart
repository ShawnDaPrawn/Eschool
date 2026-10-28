//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_redeem_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionRedeemWrite {
  /// Returns a new [AdmissionRedeemWrite] instance.
  AdmissionRedeemWrite({
    this.qrToken,

    this.ticketNumber,

    required this.operationId,

    required this.revision,

    required this.confirmed,
  });

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  @JsonKey(name: r'ticket_number', required: false, includeIfNull: false)
  final String? ticketNumber;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  /// Must be true to confirm this original ticket action.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionRedeemWrite &&
          other.qrToken == qrToken &&
          other.ticketNumber == ticketNumber &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      (qrToken == null ? 0 : qrToken.hashCode) +
      (ticketNumber == null ? 0 : ticketNumber.hashCode) +
      operationId.hashCode +
      revision.hashCode +
      confirmed.hashCode;

  factory AdmissionRedeemWrite.fromJson(Map<String, dynamic> json) =>
      _$AdmissionRedeemWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionRedeemWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
