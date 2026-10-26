//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_offline_envelope.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineSnapshot {
  /// Returns a new [SecureOfflineSnapshot] instance.
  SecureOfflineSnapshot({
    required this.requestId,

    required this.envelope,

    required this.signingKeyId,

    required this.signingPublicKey,

    required this.nextAfter,
  });

  @JsonKey(name: r'request_id', required: true, includeIfNull: false)
  final String requestId;

  @JsonKey(name: r'envelope', required: true, includeIfNull: false)
  final SecureOfflineEnvelope envelope;

  @JsonKey(name: r'signing_key_id', required: true, includeIfNull: false)
  final String signingKeyId;

  @JsonKey(name: r'signing_public_key', required: true, includeIfNull: false)
  final String signingPublicKey;

  @JsonKey(name: r'next_after', required: true, includeIfNull: true)
  final String? nextAfter;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineSnapshot &&
          other.requestId == requestId &&
          other.envelope == envelope &&
          other.signingKeyId == signingKeyId &&
          other.signingPublicKey == signingPublicKey &&
          other.nextAfter == nextAfter;

  @override
  int get hashCode =>
      requestId.hashCode +
      envelope.hashCode +
      signingKeyId.hashCode +
      signingPublicKey.hashCode +
      (nextAfter == null ? 0 : nextAfter.hashCode);

  factory SecureOfflineSnapshot.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineSnapshotFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
