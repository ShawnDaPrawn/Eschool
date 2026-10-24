// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_template_apply_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplateApplyWrite _$TermTemplateApplyWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TermTemplateApplyWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['session_year_id']);
  final val = TermTemplateApplyWrite(
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    apply: $checkedConvert('apply', (v) => v as bool? ?? false),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$TermTemplateApplyWriteToJson(
  TermTemplateApplyWrite instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'apply': ?instance.apply,
};
