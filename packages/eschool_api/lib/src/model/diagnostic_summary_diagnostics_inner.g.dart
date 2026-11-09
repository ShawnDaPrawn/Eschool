// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_summary_diagnostics_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticSummaryDiagnosticsInner _$DiagnosticSummaryDiagnosticsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DiagnosticSummaryDiagnosticsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'group_id',
        'subject_name',
        'status',
        'attempt_id',
        'can_start',
        'message',
        'focus_topics',
      ],
    );
    final val = DiagnosticSummaryDiagnosticsInner(
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectName: $checkedConvert('subject_name', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$DiagnosticStatusEnumMap,
          v,
          unknownValue: DiagnosticStatus.unknownDefaultOpenApi,
        ),
      ),
      attemptId: $checkedConvert('attempt_id', (v) => (v as num?)?.toInt()),
      canStart: $checkedConvert('can_start', (v) => v as bool),
      message: $checkedConvert('message', (v) => v as String),
      focusTopics: $checkedConvert(
        'focus_topics',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'subjectName': 'subject_name',
    'attemptId': 'attempt_id',
    'canStart': 'can_start',
    'focusTopics': 'focus_topics',
  },
);

Map<String, dynamic> _$DiagnosticSummaryDiagnosticsInnerToJson(
  DiagnosticSummaryDiagnosticsInner instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'subject_name': instance.subjectName,
  'status': _$DiagnosticStatusEnumMap[instance.status]!,
  'attempt_id': instance.attemptId,
  'can_start': instance.canStart,
  'message': instance.message,
  'focus_topics': instance.focusTopics,
};

const _$DiagnosticStatusEnumMap = {
  DiagnosticStatus.notStarted: 'not_started',
  DiagnosticStatus.inProgress: 'in_progress',
  DiagnosticStatus.completed: 'completed',
  DiagnosticStatus.unknownDefaultOpenApi: 'unknown_default_open_api',
};
