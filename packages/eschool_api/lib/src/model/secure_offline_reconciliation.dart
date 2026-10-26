//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_reconciliation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineReconciliation {
  /// Returns a new [SecureOfflineReconciliation] instance.
  SecureOfflineReconciliation({
    required this.operationId,

    required this.manifest,

    required this.credential,

    required this.scannedAt,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'manifest', required: true, includeIfNull: false)
  final String manifest;

  @JsonKey(name: r'credential', required: true, includeIfNull: false)
  final String credential;

  // minimum: 1
  // maximum: 9007199254740991
  @JsonKey(name: r'scanned_at', required: true, includeIfNull: false)
  final int scannedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineReconciliation &&
          other.operationId == operationId &&
          other.manifest == manifest &&
          other.credential == credential &&
          other.scannedAt == scannedAt;

  @override
  int get hashCode =>
      operationId.hashCode +
      manifest.hashCode +
      credential.hashCode +
      scannedAt.hashCode;

  factory SecureOfflineReconciliation.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineReconciliationFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineReconciliationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
