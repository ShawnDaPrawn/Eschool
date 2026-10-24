// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageWrite _$CommunicationMessageWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationMessageWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'title',
        'markdown',
        'channels',
        'audience',
      ],
    );
    final val = CommunicationMessageWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      markdown: $checkedConvert('markdown', (v) => v as String),
      channels: $checkedConvert(
        'channels',
        (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
      ),
      scheduledLocal: $checkedConvert('scheduled_local', (v) => v as String?),
      audience: $checkedConvert(
        'audience',
        (v) =>
            CommunicationAudienceSelection.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'scheduledLocal': 'scheduled_local',
  },
);

Map<String, dynamic> _$CommunicationMessageWriteToJson(
  CommunicationMessageWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'title': instance.title,
  'markdown': instance.markdown,
  'channels': instance.channels.toList(),
  'scheduled_local': ?instance.scheduledLocal,
  'audience': instance.audience.toJson(),
};
