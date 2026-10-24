// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_package.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitPackage _$AcademicExitPackageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitPackage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'transition_id',
        'learner_id',
        'state',
        'failure_code',
        'expires_at',
        'generated_at',
        'artifact_sha256',
        'artifact_bytes',
        'signing_public_key',
        'signing_key_id',
        'download_url',
      ],
    );
    final val = AcademicExitPackage(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      transitionId: $checkedConvert('transition_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      state: $checkedConvert('state', (v) => v as String),
      failureCode: $checkedConvert('failure_code', (v) => v as String?),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      generatedAt: $checkedConvert(
        'generated_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      artifactSha256: $checkedConvert('artifact_sha256', (v) => v as String?),
      artifactBytes: $checkedConvert(
        'artifact_bytes',
        (v) => (v as num?)?.toInt(),
      ),
      signingPublicKey: $checkedConvert(
        'signing_public_key',
        (v) => v as String?,
      ),
      signingKeyId: $checkedConvert('signing_key_id', (v) => v as String?),
      downloadUrl: $checkedConvert('download_url', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'transitionId': 'transition_id',
    'learnerId': 'learner_id',
    'failureCode': 'failure_code',
    'expiresAt': 'expires_at',
    'generatedAt': 'generated_at',
    'artifactSha256': 'artifact_sha256',
    'artifactBytes': 'artifact_bytes',
    'signingPublicKey': 'signing_public_key',
    'signingKeyId': 'signing_key_id',
    'downloadUrl': 'download_url',
  },
);

Map<String, dynamic> _$AcademicExitPackageToJson(
  AcademicExitPackage instance,
) => <String, dynamic>{
  'id': instance.id,
  'transition_id': instance.transitionId,
  'learner_id': instance.learnerId,
  'state': instance.state,
  'failure_code': instance.failureCode,
  'expires_at': instance.expiresAt.toIso8601String(),
  'generated_at': instance.generatedAt?.toIso8601String(),
  'artifact_sha256': instance.artifactSha256,
  'artifact_bytes': instance.artifactBytes,
  'signing_public_key': instance.signingPublicKey,
  'signing_key_id': instance.signingKeyId,
  'download_url': instance.downloadUrl,
};
