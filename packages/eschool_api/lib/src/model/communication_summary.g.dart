// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationSummary _$CommunicationSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationSummary',
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
      ],
    );
    final val = CommunicationSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$CommunicationSummaryStateEnumEnumMap,
          v,
          unknownValue: CommunicationSummaryStateEnum.unknownDefaultOpenApi,
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
      readAt: $checkedConvert(
        'read_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      isAuthor: $checkedConvert('is_author', (v) => v as bool?),
      canReview: $checkedConvert('can_review', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {
    'publishedAt': 'published_at',
    'scheduledAt': 'scheduled_at',
    'updatedAt': 'updated_at',
    'readAt': 'read_at',
    'isAuthor': 'is_author',
    'canReview': 'can_review',
  },
);

Map<String, dynamic> _$CommunicationSummaryToJson(
  CommunicationSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'state': _$CommunicationSummaryStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'published_at': instance.publishedAt?.toIso8601String(),
  'scheduled_at': instance.scheduledAt?.toIso8601String(),
  'updated_at': instance.updatedAt.toIso8601String(),
  'read_at': ?instance.readAt?.toIso8601String(),
  'is_author': ?instance.isAuthor,
  'can_review': ?instance.canReview,
};

const _$CommunicationSummaryStateEnumEnumMap = {
  CommunicationSummaryStateEnum.draft: 'draft',
  CommunicationSummaryStateEnum.pending: 'pending',
  CommunicationSummaryStateEnum.approved: 'approved',
  CommunicationSummaryStateEnum.dispatching: 'dispatching',
  CommunicationSummaryStateEnum.sent: 'sent',
  CommunicationSummaryStateEnum.rejected: 'rejected',
  CommunicationSummaryStateEnum.blocked: 'blocked',
  CommunicationSummaryStateEnum.withdrawn: 'withdrawn',
  CommunicationSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
