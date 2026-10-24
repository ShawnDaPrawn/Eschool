//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_package.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitPackage {
  /// Returns a new [AcademicExitPackage] instance.
  AcademicExitPackage({
    required this.id,

    required this.transitionId,

    required this.learnerId,

    required this.state,

    required this.failureCode,

    required this.expiresAt,

    required this.generatedAt,

    required this.artifactSha256,

    required this.artifactBytes,

    required this.signingPublicKey,

    required this.signingKeyId,

    required this.downloadUrl,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'transition_id', required: true, includeIfNull: false)
  final int transitionId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'generated_at', required: true, includeIfNull: true)
  final DateTime? generatedAt;

  @JsonKey(name: r'artifact_sha256', required: true, includeIfNull: true)
  final String? artifactSha256;

  @JsonKey(name: r'artifact_bytes', required: true, includeIfNull: true)
  final int? artifactBytes;

  @JsonKey(name: r'signing_public_key', required: true, includeIfNull: true)
  final String? signingPublicKey;

  @JsonKey(name: r'signing_key_id', required: true, includeIfNull: true)
  final String? signingKeyId;

  @JsonKey(name: r'download_url', required: true, includeIfNull: true)
  final String? downloadUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitPackage &&
          other.id == id &&
          other.transitionId == transitionId &&
          other.learnerId == learnerId &&
          other.state == state &&
          other.failureCode == failureCode &&
          other.expiresAt == expiresAt &&
          other.generatedAt == generatedAt &&
          other.artifactSha256 == artifactSha256 &&
          other.artifactBytes == artifactBytes &&
          other.signingPublicKey == signingPublicKey &&
          other.signingKeyId == signingKeyId &&
          other.downloadUrl == downloadUrl;

  @override
  int get hashCode =>
      id.hashCode +
      transitionId.hashCode +
      learnerId.hashCode +
      state.hashCode +
      (failureCode == null ? 0 : failureCode.hashCode) +
      expiresAt.hashCode +
      (generatedAt == null ? 0 : generatedAt.hashCode) +
      (artifactSha256 == null ? 0 : artifactSha256.hashCode) +
      (artifactBytes == null ? 0 : artifactBytes.hashCode) +
      (signingPublicKey == null ? 0 : signingPublicKey.hashCode) +
      (signingKeyId == null ? 0 : signingKeyId.hashCode) +
      (downloadUrl == null ? 0 : downloadUrl.hashCode);

  factory AcademicExitPackage.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitPackageFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitPackageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
