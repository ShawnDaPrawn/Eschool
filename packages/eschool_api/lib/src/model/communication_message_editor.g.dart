// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_editor.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageEditor _$CommunicationMessageEditorFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationMessageEditor',
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
        'markdown',
        'audience',
        'channels',
        'scheduled_local',
        'requires_approval',
        'submitted_at',
        'approved_at',
        'decision_reason',
        'blocked_code',
        'withdrawal_reason',
        'is_author',
        'can_review',
        'recipient_count',
        'read_count',
      ],
    );
    final val = CommunicationMessageEditor(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$CommunicationMessageEditorStateEnumEnumMap,
          v,
          unknownValue:
              CommunicationMessageEditorStateEnum.unknownDefaultOpenApi,
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
      markdown: $checkedConvert('markdown', (v) => v as String),
      audience: $checkedConvert(
        'audience',
        (v) =>
            CommunicationAudienceSnapshot.fromJson(v as Map<String, dynamic>),
      ),
      channels: $checkedConvert(
        'channels',
        (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
      ),
      scheduledLocal: $checkedConvert('scheduled_local', (v) => v as String?),
      requiresApproval: $checkedConvert('requires_approval', (v) => v as bool),
      submittedAt: $checkedConvert(
        'submitted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      approvedAt: $checkedConvert(
        'approved_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      decisionReason: $checkedConvert('decision_reason', (v) => v as String?),
      blockedCode: $checkedConvert('blocked_code', (v) => v as String?),
      withdrawalReason: $checkedConvert(
        'withdrawal_reason',
        (v) => v as String?,
      ),
      isAuthor: $checkedConvert('is_author', (v) => v as bool),
      canReview: $checkedConvert('can_review', (v) => v as bool),
      recipientCount: $checkedConvert(
        'recipient_count',
        (v) => (v as num).toInt(),
      ),
      readCount: $checkedConvert('read_count', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'publishedAt': 'published_at',
    'scheduledAt': 'scheduled_at',
    'updatedAt': 'updated_at',
    'scheduledLocal': 'scheduled_local',
    'requiresApproval': 'requires_approval',
    'submittedAt': 'submitted_at',
    'approvedAt': 'approved_at',
    'decisionReason': 'decision_reason',
    'blockedCode': 'blocked_code',
    'withdrawalReason': 'withdrawal_reason',
    'isAuthor': 'is_author',
    'canReview': 'can_review',
    'recipientCount': 'recipient_count',
    'readCount': 'read_count',
  },
);

Map<String, dynamic> _$CommunicationMessageEditorToJson(
  CommunicationMessageEditor instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'state': _$CommunicationMessageEditorStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'published_at': instance.publishedAt?.toIso8601String(),
  'scheduled_at': instance.scheduledAt?.toIso8601String(),
  'updated_at': instance.updatedAt.toIso8601String(),
  'markdown': instance.markdown,
  'audience': instance.audience.toJson(),
  'channels': instance.channels.toList(),
  'scheduled_local': instance.scheduledLocal,
  'requires_approval': instance.requiresApproval,
  'submitted_at': instance.submittedAt?.toIso8601String(),
  'approved_at': instance.approvedAt?.toIso8601String(),
  'decision_reason': instance.decisionReason,
  'blocked_code': instance.blockedCode,
  'withdrawal_reason': instance.withdrawalReason,
  'is_author': instance.isAuthor,
  'can_review': instance.canReview,
  'recipient_count': instance.recipientCount,
  'read_count': instance.readCount,
};

const _$CommunicationMessageEditorStateEnumEnumMap = {
  CommunicationMessageEditorStateEnum.draft: 'draft',
  CommunicationMessageEditorStateEnum.pending: 'pending',
  CommunicationMessageEditorStateEnum.approved: 'approved',
  CommunicationMessageEditorStateEnum.dispatching: 'dispatching',
  CommunicationMessageEditorStateEnum.sent: 'sent',
  CommunicationMessageEditorStateEnum.rejected: 'rejected',
  CommunicationMessageEditorStateEnum.blocked: 'blocked',
  CommunicationMessageEditorStateEnum.withdrawn: 'withdrawn',
  CommunicationMessageEditorStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
