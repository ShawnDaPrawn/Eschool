// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperSummary _$ExamCoachPaperSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachPaperSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'version_id',
        'number',
        'title',
        'paper_year',
        'paper_code',
        'state',
        'licence_kind',
        'revision',
        'can_edit',
      ],
    );
    final val = ExamCoachPaperSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      versionId: $checkedConvert('version_id', (v) => v as String),
      number: $checkedConvert('number', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      paperYear: $checkedConvert('paper_year', (v) => (v as num).toInt()),
      paperCode: $checkedConvert('paper_code', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$ExamCoachPaperSummaryStateEnumEnumMap,
          v,
          unknownValue: ExamCoachPaperSummaryStateEnum.unknownDefaultOpenApi,
        ),
      ),
      licenceKind: $checkedConvert(
        'licence_kind',
        (v) => $enumDecode(
          _$ExamCoachPaperSummaryLicenceKindEnumEnumMap,
          v,
          unknownValue:
              ExamCoachPaperSummaryLicenceKindEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      canEdit: $checkedConvert('can_edit', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'versionId': 'version_id',
    'paperYear': 'paper_year',
    'paperCode': 'paper_code',
    'licenceKind': 'licence_kind',
    'canEdit': 'can_edit',
  },
);

Map<String, dynamic> _$ExamCoachPaperSummaryToJson(
  ExamCoachPaperSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'version_id': instance.versionId,
  'number': instance.number,
  'title': instance.title,
  'paper_year': instance.paperYear,
  'paper_code': instance.paperCode,
  'state': _$ExamCoachPaperSummaryStateEnumEnumMap[instance.state]!,
  'licence_kind':
      _$ExamCoachPaperSummaryLicenceKindEnumEnumMap[instance.licenceKind]!,
  'revision': instance.revision,
  'can_edit': instance.canEdit,
};

const _$ExamCoachPaperSummaryStateEnumEnumMap = {
  ExamCoachPaperSummaryStateEnum.draft: 'draft',
  ExamCoachPaperSummaryStateEnum.published: 'published',
  ExamCoachPaperSummaryStateEnum.withdrawn: 'withdrawn',
  ExamCoachPaperSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ExamCoachPaperSummaryLicenceKindEnumEnumMap = {
  ExamCoachPaperSummaryLicenceKindEnum.schoolOwned: 'school_owned',
  ExamCoachPaperSummaryLicenceKindEnum.openLicence: 'open_licence',
  ExamCoachPaperSummaryLicenceKindEnum.permission: 'permission',
  ExamCoachPaperSummaryLicenceKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
