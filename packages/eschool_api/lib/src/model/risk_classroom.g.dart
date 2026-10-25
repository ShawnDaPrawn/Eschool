// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_classroom.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskClassroom _$RiskClassroomFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskClassroom', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['group_id', 'learners']);
      final val = RiskClassroom(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        learners: $checkedConvert(
          'learners',
          (v) => (v as List<dynamic>)
              .map(
                (e) => RiskClassroomLearner.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id'});

Map<String, dynamic> _$RiskClassroomToJson(RiskClassroom instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
