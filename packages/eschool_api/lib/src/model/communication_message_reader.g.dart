// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_reader.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReader _$CommunicationMessageReaderFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationMessageReader',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'title',
        'state',
        'revision',
        'published_at',
        'scheduled_at',
        'updated_at',
        'school_id',
        'reader_id',
        'markdown',
        'read_at',
      ],
    );
    final val = CommunicationMessageReader(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$CommunicationMessageReaderStateEnumEnumMap,
          v,
          unknownValue:
              CommunicationMessageReaderStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      publishedAt: $checkedConvert(
        'published_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      scheduledAt: $checkedConvert(
        'scheduled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      updatedAt: $checkedConvert(
        'updated_at',
        (v) => DateTime.parse(v as String),
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      markdown: $checkedConvert('markdown', (v) => v as String),
      readAt: $checkedConvert(
        'read_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'publishedAt': 'published_at',
    'scheduledAt': 'scheduled_at',
    'updatedAt': 'updated_at',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'readAt': 'read_at',
  },
);

Map<String, dynamic> _$CommunicationMessageReaderToJson(
  CommunicationMessageReader instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'state': _$CommunicationMessageReaderStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'published_at': instance.publishedAt?.toIso8601String(),
  'scheduled_at': instance.scheduledAt?.toIso8601String(),
  'updated_at': instance.updatedAt.toIso8601String(),
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'markdown': instance.markdown,
  'read_at': instance.readAt?.toIso8601String(),
};

const _$CommunicationMessageReaderStateEnumEnumMap = {
  CommunicationMessageReaderStateEnum.draft: 'draft',
  CommunicationMessageReaderStateEnum.pending: 'pending',
  CommunicationMessageReaderStateEnum.approved: 'approved',
  CommunicationMessageReaderStateEnum.dispatching: 'dispatching',
  CommunicationMessageReaderStateEnum.sent: 'sent',
  CommunicationMessageReaderStateEnum.rejected: 'rejected',
  CommunicationMessageReaderStateEnum.blocked: 'blocked',
  CommunicationMessageReaderStateEnum.withdrawn: 'withdrawn',
  CommunicationMessageReaderStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
