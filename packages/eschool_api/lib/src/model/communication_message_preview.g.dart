// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessagePreview _$CommunicationMessagePreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationMessagePreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'recipient_count',
        'requires_approval',
        'fingerprint',
        'html',
        'channels',
        'scheduled_at',
        'timezone',
      ],
    );
    final val = CommunicationMessagePreview(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      recipientCount: $checkedConvert(
        'recipient_count',
        (v) => (v as num).toInt(),
      ),
      requiresApproval: $checkedConvert('requires_approval', (v) => v as bool),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      html: $checkedConvert('html', (v) => v as String),
      channels: $checkedConvert(
        'channels',
        (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
      ),
      scheduledAt: $checkedConvert(
        'scheduled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      timezone: $checkedConvert('timezone', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'recipientCount': 'recipient_count',
    'requiresApproval': 'requires_approval',
    'scheduledAt': 'scheduled_at',
  },
);

Map<String, dynamic> _$CommunicationMessagePreviewToJson(
  CommunicationMessagePreview instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'recipient_count': instance.recipientCount,
  'requires_approval': instance.requiresApproval,
  'fingerprint': instance.fingerprint,
  'html': instance.html,
  'channels': instance.channels.toList(),
  'scheduled_at': instance.scheduledAt?.toIso8601String(),
  'timezone': instance.timezone,
};
