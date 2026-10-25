// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAssignment _$WellbeingAssignmentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WellbeingAssignment',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'user_id',
            'name',
            'starts_on',
            'ends_on',
            'active',
            'revision',
          ],
        );
        final val = WellbeingAssignment(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          active: $checkedConvert('active', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'userId': 'user_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$WellbeingAssignmentToJson(
  WellbeingAssignment instance,
) => <String, dynamic>{
  'id': instance.id,
  'user_id': instance.userId,
  'name': instance.name,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'active': instance.active,
  'revision': instance.revision,
};
