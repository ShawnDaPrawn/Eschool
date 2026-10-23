// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationPreview _$GuardianInvitationPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GuardianInvitationPreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school',
        'email',
        'learners',
        'expires_at',
        'needs_password',
      ],
    );
    final val = GuardianInvitationPreview(
      school: $checkedConvert(
        'school',
        (v) => SchoolSummary.fromJson(v as Map<String, dynamic>),
      ),
      email: $checkedConvert('email', (v) => v as String),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) => GuardianInvitationPreviewLearnersInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      needsPassword: $checkedConvert('needs_password', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'expiresAt': 'expires_at',
    'needsPassword': 'needs_password',
  },
);

Map<String, dynamic> _$GuardianInvitationPreviewToJson(
  GuardianInvitationPreview instance,
) => <String, dynamic>{
  'school': instance.school.toJson(),
  'email': instance.email,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
  'expires_at': instance.expiresAt.toIso8601String(),
  'needs_password': instance.needsPassword,
};
