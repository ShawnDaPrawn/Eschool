//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_encoding_confirmation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureEncodingConfirmation {
  /// Returns a new [SecureEncodingConfirmation] instance.
  SecureEncodingConfirmation({
    required this.operationId,

    required this.picc,

    required this.mac,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'picc', required: true, includeIfNull: false)
  final String picc;

  @JsonKey(name: r'mac', required: true, includeIfNull: false)
  final String mac;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureEncodingConfirmation &&
          other.operationId == operationId &&
          other.picc == picc &&
          other.mac == mac;

  @override
  int get hashCode => operationId.hashCode + picc.hashCode + mac.hashCode;

  factory SecureEncodingConfirmation.fromJson(Map<String, dynamic> json) =>
      _$SecureEncodingConfirmationFromJson(json);

  Map<String, dynamic> toJson() => _$SecureEncodingConfirmationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
