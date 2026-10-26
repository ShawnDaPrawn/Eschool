// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_template.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestTemplate _$WeeklyDigestTemplateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestTemplate', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'language',
      'version',
      'revision',
      'title',
      'body',
      'actions',
      'notice_prompt',
      'approved',
    ],
  );
  final val = WeeklyDigestTemplate(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    language: $checkedConvert('language', (v) => v as String),
    version: $checkedConvert('version', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    title: $checkedConvert('title', (v) => v as String),
    body: $checkedConvert('body', (v) => v as String),
    actions: $checkedConvert(
      'actions',
      (v) => WeeklyDigestActionTemplates.fromJson(v as Map<String, dynamic>),
    ),
    noticePrompt: $checkedConvert('notice_prompt', (v) => v as String),
    approved: $checkedConvert('approved', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'noticePrompt': 'notice_prompt'});

Map<String, dynamic> _$WeeklyDigestTemplateToJson(
  WeeklyDigestTemplate instance,
) => <String, dynamic>{
  'id': instance.id,
  'language': instance.language,
  'version': instance.version,
  'revision': instance.revision,
  'title': instance.title,
  'body': instance.body,
  'actions': instance.actions.toJson(),
  'notice_prompt': instance.noticePrompt,
  'approved': instance.approved,
};
