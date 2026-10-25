// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_bank_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeBankItem _$PracticeBankItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeBankItem',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'topic_id',
            'revision',
            'active',
            'content_version_id',
            'estimated_seconds',
            'definition',
          ],
        );
        final val = PracticeBankItem(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          active: $checkedConvert('active', (v) => v as bool),
          contentVersionId: $checkedConvert(
            'content_version_id',
            (v) => (v as num).toInt(),
          ),
          estimatedSeconds: $checkedConvert(
            'estimated_seconds',
            (v) => (v as num).toInt(),
          ),
          definition: $checkedConvert(
            'definition',
            (v) => PracticeQuestion.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'topicId': 'topic_id',
        'contentVersionId': 'content_version_id',
        'estimatedSeconds': 'estimated_seconds',
      },
    );

Map<String, dynamic> _$PracticeBankItemToJson(PracticeBankItem instance) =>
    <String, dynamic>{
      'id': instance.id,
      'topic_id': instance.topicId,
      'revision': instance.revision,
      'active': instance.active,
      'content_version_id': instance.contentVersionId,
      'estimated_seconds': instance.estimatedSeconds,
      'definition': instance.definition.toJson(),
    };
