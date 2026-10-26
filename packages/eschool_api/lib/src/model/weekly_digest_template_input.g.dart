// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_template_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestTemplateInput _$WeeklyDigestTemplateInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WeeklyDigestTemplateInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'language',
        'title',
        'body',
        'actions',
        'notice_prompt',
      ],
    );
    final val = WeeklyDigestTemplateInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      language: $checkedConvert('language', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      body: $checkedConvert('body', (v) => v as String),
      actions: $checkedConvert(
        'actions',
        (v) => WeeklyDigestActionTemplates.fromJson(v as Map<String, dynamic>),
      ),
      noticePrompt: $checkedConvert('notice_prompt', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'noticePrompt': 'notice_prompt',
  },
);

Map<String, dynamic> _$WeeklyDigestTemplateInputToJson(
  WeeklyDigestTemplateInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'language': instance.language,
  'title': instance.title,
  'body': instance.body,
  'actions': instance.actions.toJson(),
  'notice_prompt': instance.noticePrompt,
};
