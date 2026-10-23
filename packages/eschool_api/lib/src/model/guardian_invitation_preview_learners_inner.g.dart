// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_preview_learners_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationPreviewLearnersInner
_$GuardianInvitationPreviewLearnersInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GuardianInvitationPreviewLearnersInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = GuardianInvitationPreviewLearnersInner(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$GuardianInvitationPreviewLearnersInnerToJson(
  GuardianInvitationPreviewLearnersInner instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
