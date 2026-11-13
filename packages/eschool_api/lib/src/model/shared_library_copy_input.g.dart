// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_copy_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCopyInput _$SharedLibraryCopyInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryCopyInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'group_id',
        'topic_id',
        'content_version_id',
      ],
    );
    final val = SharedLibraryCopyInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      contentVersionId: $checkedConvert(
        'content_version_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'groupId': 'group_id',
    'topicId': 'topic_id',
    'contentVersionId': 'content_version_id',
  },
);

Map<String, dynamic> _$SharedLibraryCopyInputToJson(
  SharedLibraryCopyInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'group_id': instance.groupId,
  'topic_id': instance.topicId,
  'content_version_id': instance.contentVersionId,
};
