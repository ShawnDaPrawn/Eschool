// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'task_insight_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TaskInsightDetail _$TaskInsightDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TaskInsightDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'id',
            'fingerprint',
            'computed_at',
            'summary',
          ],
        );
        final val = TaskInsightDetail(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          computedAt: $checkedConvert(
            'computed_at',
            (v) => DateTime.parse(v as String),
          ),
          summary: $checkedConvert(
            'summary',
            (v) => TaskInsightSummary.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'computedAt': 'computed_at',
      },
    );

Map<String, dynamic> _$TaskInsightDetailToJson(TaskInsightDetail instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'id': instance.id,
      'fingerprint': instance.fingerprint,
      'computed_at': instance.computedAt.toIso8601String(),
      'summary': instance.summary.toJson(),
    };
