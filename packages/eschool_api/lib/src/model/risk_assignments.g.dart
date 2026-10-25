// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignments.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignments _$RiskAssignmentsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskAssignments',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['learner_id', 'session_year_id', 'assignments'],
        );
        final val = RiskAssignments(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          assignments: $checkedConvert(
            'assignments',
            (v) => (v as List<dynamic>)
                .map((e) => RiskAssignment.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'sessionYearId': 'session_year_id',
      },
    );

Map<String, dynamic> _$RiskAssignmentsToJson(RiskAssignments instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'session_year_id': instance.sessionYearId,
      'assignments': instance.assignments.map((e) => e.toJson()).toList(),
    };
