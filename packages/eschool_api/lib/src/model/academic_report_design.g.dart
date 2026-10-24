// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_design.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportDesign _$AcademicReportDesignFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportDesign', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'heading',
      'introduction',
      'footer',
      'sections',
      'show_crest',
    ],
  );
  final val = AcademicReportDesign(
    heading: $checkedConvert('heading', (v) => v as String),
    introduction: $checkedConvert('introduction', (v) => v as String?),
    footer: $checkedConvert('footer', (v) => v as String?),
    sections: $checkedConvert(
      'sections',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    showCrest: $checkedConvert('show_crest', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'showCrest': 'show_crest'});

Map<String, dynamic> _$AcademicReportDesignToJson(
  AcademicReportDesign instance,
) => <String, dynamic>{
  'heading': instance.heading,
  'introduction': instance.introduction,
  'footer': instance.footer,
  'sections': instance.sections,
  'show_crest': instance.showCrest,
};
