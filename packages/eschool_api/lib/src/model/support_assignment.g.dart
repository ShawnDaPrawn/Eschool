// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportAssignment _$SupportAssignmentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportAssignment',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'learner_name',
            'user_id',
            'professional_name',
            'starts_on',
            'ends_on',
            'active',
            'revision',
          ],
        );
        final val = SupportAssignment(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
          professionalName: $checkedConvert(
            'professional_name',
            (v) => v as String,
          ),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          active: $checkedConvert('active', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'userId': 'user_id',
        'professionalName': 'professional_name',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$SupportAssignmentToJson(SupportAssignment instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'user_id': instance.userId,
      'professional_name': instance.professionalName,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'active': instance.active,
      'revision': instance.revision,
    };
