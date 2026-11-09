// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_group_learners_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticGroupLearnersInner _$DiagnosticGroupLearnersInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiagnosticGroupLearnersInner', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['learner_id', 'name', 'status', 'topics'],
  );
  final val = DiagnosticGroupLearnersInner(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$DiagnosticStatusEnumMap,
        v,
        unknownValue: DiagnosticStatus.unknownDefaultOpenApi,
      ),
    ),
    topics: $checkedConvert(
      'topics',
      (v) => (v as List<dynamic>)
          .map(
            (e) => DiagnosticGroupLearnersInnerTopicsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$DiagnosticGroupLearnersInnerToJson(
  DiagnosticGroupLearnersInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'status': _$DiagnosticStatusEnumMap[instance.status]!,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
};

const _$DiagnosticStatusEnumMap = {
  DiagnosticStatus.notStarted: 'not_started',
  DiagnosticStatus.inProgress: 'in_progress',
  DiagnosticStatus.completed: 'completed',
  DiagnosticStatus.unknownDefaultOpenApi: 'unknown_default_open_api',
};
