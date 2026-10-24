// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentPreview _$ContentPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ContentPreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'content_item_id',
        'item_revision',
        'version_revision',
        'selection',
        'markdown',
        'fingerprint',
        'learner_count',
        'review_required',
        'requires_reason',
      ],
    );
    final val = ContentPreview(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      contentItemId: $checkedConvert(
        'content_item_id',
        (v) => (v as num).toInt(),
      ),
      itemRevision: $checkedConvert('item_revision', (v) => (v as num).toInt()),
      versionRevision: $checkedConvert(
        'version_revision',
        (v) => (v as num).toInt(),
      ),
      selection: $checkedConvert(
        'selection',
        (v) => ContentSelection.fromJson(v as Map<String, dynamic>),
      ),
      markdown: $checkedConvert('markdown', (v) => v as String),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      learnerCount: $checkedConvert('learner_count', (v) => (v as num).toInt()),
      reviewRequired: $checkedConvert('review_required', (v) => v as bool),
      requiresReason: $checkedConvert('requires_reason', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'contentItemId': 'content_item_id',
    'itemRevision': 'item_revision',
    'versionRevision': 'version_revision',
    'learnerCount': 'learner_count',
    'reviewRequired': 'review_required',
    'requiresReason': 'requires_reason',
  },
);

Map<String, dynamic> _$ContentPreviewToJson(ContentPreview instance) =>
    <String, dynamic>{
      'id': instance.id,
      'content_item_id': instance.contentItemId,
      'item_revision': instance.itemRevision,
      'version_revision': instance.versionRevision,
      'selection': instance.selection.toJson(),
      'markdown': instance.markdown,
      'fingerprint': instance.fingerprint,
      'learner_count': instance.learnerCount,
      'review_required': instance.reviewRequired,
      'requires_reason': instance.requiresReason,
    };
