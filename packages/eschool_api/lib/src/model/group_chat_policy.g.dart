// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_policy.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatPolicy _$GroupChatPolicyFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatPolicy',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'revision',
            'staff_learners',
            'staff_guardians',
            'staff_staff',
            'learner_shared_replies',
            'guardian_shared_replies',
          ],
        );
        final val = GroupChatPolicy(
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          staffLearners: $checkedConvert('staff_learners', (v) => v as bool),
          staffGuardians: $checkedConvert('staff_guardians', (v) => v as bool),
          staffStaff: $checkedConvert('staff_staff', (v) => v as bool),
          learnerSharedReplies: $checkedConvert(
            'learner_shared_replies',
            (v) => v as bool,
          ),
          guardianSharedReplies: $checkedConvert(
            'guardian_shared_replies',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'staffLearners': 'staff_learners',
        'staffGuardians': 'staff_guardians',
        'staffStaff': 'staff_staff',
        'learnerSharedReplies': 'learner_shared_replies',
        'guardianSharedReplies': 'guardian_shared_replies',
      },
    );

Map<String, dynamic> _$GroupChatPolicyToJson(GroupChatPolicy instance) =>
    <String, dynamic>{
      'revision': instance.revision,
      'staff_learners': instance.staffLearners,
      'staff_guardians': instance.staffGuardians,
      'staff_staff': instance.staffStaff,
      'learner_shared_replies': instance.learnerSharedReplies,
      'guardian_shared_replies': instance.guardianSharedReplies,
    };
