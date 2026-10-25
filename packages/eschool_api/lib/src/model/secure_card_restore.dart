//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_restore.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardRestore {
  /// Returns a new [SecureCardRestore] instance.
  SecureCardRestore({
    required this.operationId,

    required this.reason,

    this.picc,

    this.mac,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'picc', required: false, includeIfNull: false)
  final String? picc;

  @JsonKey(name: r'mac', required: false, includeIfNull: false)
  final String? mac;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardRestore &&
          other.operationId == operationId &&
          other.reason == reason &&
          other.picc == picc &&
          other.mac == mac;

  @override
  int get hashCode =>
      operationId.hashCode + reason.hashCode + picc.hashCode + mac.hashCode;

  factory SecureCardRestore.fromJson(Map<String, dynamic> json) =>
      _$SecureCardRestoreFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardRestoreToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
