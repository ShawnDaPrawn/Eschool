// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_approve_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeApproveInput _$PracticeApproveInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeApproveInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'topic_id',
        'content_version_id',
        'estimated_seconds',
        'active',
        'definition',
      ],
    );
    final val = PracticeApproveInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      contentVersionId: $checkedConvert(
        'content_version_id',
        (v) => (v as num).toInt(),
      ),
      estimatedSeconds: $checkedConvert(
        'estimated_seconds',
        (v) => (v as num).toInt(),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      definition: $checkedConvert(
        'definition',
        (v) => PracticeQuestion.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'topicId': 'topic_id',
    'contentVersionId': 'content_version_id',
    'estimatedSeconds': 'estimated_seconds',
  },
);

Map<String, dynamic> _$PracticeApproveInputToJson(
  PracticeApproveInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'topic_id': instance.topicId,
  'content_version_id': instance.contentVersionId,
  'estimated_seconds': instance.estimatedSeconds,
  'active': instance.active,
  'definition': instance.definition.toJson(),
};
