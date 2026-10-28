//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_lookup_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionLookupWrite {
  /// Returns a new [AdmissionLookupWrite] instance.
  AdmissionLookupWrite({this.qrToken, this.ticketNumber});

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  @JsonKey(name: r'ticket_number', required: false, includeIfNull: false)
  final String? ticketNumber;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionLookupWrite &&
          other.qrToken == qrToken &&
          other.ticketNumber == ticketNumber;

  @override
  int get hashCode =>
      (qrToken == null ? 0 : qrToken.hashCode) +
      (ticketNumber == null ? 0 : ticketNumber.hashCode);

  factory AdmissionLookupWrite.fromJson(Map<String, dynamic> json) =>
      _$AdmissionLookupWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionLookupWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
