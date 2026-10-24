// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_template_apply_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplateApplyResponseData _$TermTemplateApplyResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TermTemplateApplyResponseData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'applied',
        'session_year_id',
        'template_id',
        'created_count',
        'would_create_count',
        'skipped_count',
        'terms',
      ],
    );
    final val = TermTemplateApplyResponseData(
      applied: $checkedConvert('applied', (v) => v as bool),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      templateId: $checkedConvert('template_id', (v) => (v as num).toInt()),
      createdCount: $checkedConvert('created_count', (v) => (v as num).toInt()),
      wouldCreateCount: $checkedConvert(
        'would_create_count',
        (v) => (v as num).toInt(),
      ),
      skippedCount: $checkedConvert('skipped_count', (v) => (v as num).toInt()),
      terms: $checkedConvert(
        'terms',
        (v) => (v as List<dynamic>)
            .map((e) => SchoolTermDraft.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'templateId': 'template_id',
    'createdCount': 'created_count',
    'wouldCreateCount': 'would_create_count',
    'skippedCount': 'skipped_count',
  },
);

Map<String, dynamic> _$TermTemplateApplyResponseDataToJson(
  TermTemplateApplyResponseData instance,
) => <String, dynamic>{
  'applied': instance.applied,
  'session_year_id': instance.sessionYearId,
  'template_id': instance.templateId,
  'created_count': instance.createdCount,
  'would_create_count': instance.wouldCreateCount,
  'skipped_count': instance.skippedCount,
  'terms': instance.terms.map((e) => e.toJson()).toList(),
};
