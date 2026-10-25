// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_topic_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryTopicView _$MasteryTopicViewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MasteryTopicView',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'topic_key',
            'name',
            'grade_level_id',
            'grade_name',
            'level',
            'confidence_bp',
            'evidence_count',
            'computed_at',
          ],
        );
        final val = MasteryTopicView(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          topicKey: $checkedConvert('topic_key', (v) => v as String),
          name: $checkedConvert('name', (v) => v as String),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          gradeName: $checkedConvert('grade_name', (v) => v as String),
          level: $checkedConvert('level', (v) => (v as num?)?.toInt()),
          confidenceBp: $checkedConvert(
            'confidence_bp',
            (v) => (v as num).toInt(),
          ),
          evidenceCount: $checkedConvert(
            'evidence_count',
            (v) => (v as num).toInt(),
          ),
          computedAt: $checkedConvert(
            'computed_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'topicKey': 'topic_key',
        'gradeLevelId': 'grade_level_id',
        'gradeName': 'grade_name',
        'confidenceBp': 'confidence_bp',
        'evidenceCount': 'evidence_count',
        'computedAt': 'computed_at',
      },
    );

Map<String, dynamic> _$MasteryTopicViewToJson(MasteryTopicView instance) =>
    <String, dynamic>{
      'id': instance.id,
      'topic_key': instance.topicKey,
      'name': instance.name,
      'grade_level_id': instance.gradeLevelId,
      'grade_name': instance.gradeName,
      'level': instance.level,
      'confidence_bp': instance.confidenceBp,
      'evidence_count': instance.evidenceCount,
      'computed_at': instance.computedAt?.toIso8601String(),
    };
