//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_qr_scan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardQrScan {
  /// Returns a new [SecureCardQrScan] instance.
  SecureCardQrScan({required this.operationId, required this.credential});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'credential', required: true, includeIfNull: false)
  final String credential;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardQrScan &&
          other.operationId == operationId &&
          other.credential == credential;

  @override
  int get hashCode => operationId.hashCode + credential.hashCode;

  factory SecureCardQrScan.fromJson(Map<String, dynamic> json) =>
      _$SecureCardQrScanFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardQrScanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
