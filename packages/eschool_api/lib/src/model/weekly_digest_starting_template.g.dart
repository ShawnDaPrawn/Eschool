// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_starting_template.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestStartingTemplate _$WeeklyDigestStartingTemplateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestStartingTemplate', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['title', 'body', 'actions', 'notice_prompt'],
  );
  final val = WeeklyDigestStartingTemplate(
    title: $checkedConvert('title', (v) => v as String),
    body: $checkedConvert('body', (v) => v as String),
    actions: $checkedConvert(
      'actions',
      (v) => WeeklyDigestActionTemplates.fromJson(v as Map<String, dynamic>),
    ),
    noticePrompt: $checkedConvert('notice_prompt', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'noticePrompt': 'notice_prompt'});

Map<String, dynamic> _$WeeklyDigestStartingTemplateToJson(
  WeeklyDigestStartingTemplate instance,
) => <String, dynamic>{
  'title': instance.title,
  'body': instance.body,
  'actions': instance.actions.toJson(),
  'notice_prompt': instance.noticePrompt,
};
