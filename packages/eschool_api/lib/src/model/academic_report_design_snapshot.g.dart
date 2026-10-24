// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_design_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportDesignSnapshot _$AcademicReportDesignSnapshotFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportDesignSnapshot',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'template_id',
        'template_revision',
        'name',
        'heading',
        'introduction',
        'footer',
        'sections',
        'show_crest',
        'crest',
      ],
    );
    final val = AcademicReportDesignSnapshot(
      templateId: $checkedConvert('template_id', (v) => (v as num).toInt()),
      templateRevision: $checkedConvert(
        'template_revision',
        (v) => (v as num).toInt(),
      ),
      name: $checkedConvert('name', (v) => v as String),
      heading: $checkedConvert('heading', (v) => v as String),
      introduction: $checkedConvert('introduction', (v) => v as String?),
      footer: $checkedConvert('footer', (v) => v as String?),
      sections: $checkedConvert(
        'sections',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      showCrest: $checkedConvert('show_crest', (v) => v as bool),
      crest: $checkedConvert('crest', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'templateId': 'template_id',
    'templateRevision': 'template_revision',
    'showCrest': 'show_crest',
  },
);

Map<String, dynamic> _$AcademicReportDesignSnapshotToJson(
  AcademicReportDesignSnapshot instance,
) => <String, dynamic>{
  'template_id': instance.templateId,
  'template_revision': instance.templateRevision,
  'name': instance.name,
  'heading': instance.heading,
  'introduction': instance.introduction,
  'footer': instance.footer,
  'sections': instance.sections,
  'show_crest': instance.showCrest,
  'crest': instance.crest,
};
