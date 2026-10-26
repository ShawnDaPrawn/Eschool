// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_version.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperVersion _$ExamCoachPaperVersionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachPaperVersion',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'version_id',
        'number',
        'title',
        'framework',
        'paper_year',
        'paper_code',
        'state',
        'licence_kind',
        'licence_reference',
        'questions',
      ],
    );
    final val = ExamCoachPaperVersion(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      versionId: $checkedConvert('version_id', (v) => v as String),
      number: $checkedConvert('number', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      framework: $checkedConvert('framework', (v) => v as String),
      paperYear: $checkedConvert('paper_year', (v) => (v as num).toInt()),
      paperCode: $checkedConvert('paper_code', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$ExamCoachPaperVersionStateEnumEnumMap,
          v,
          unknownValue: ExamCoachPaperVersionStateEnum.unknownDefaultOpenApi,
        ),
      ),
      licenceKind: $checkedConvert(
        'licence_kind',
        (v) => $enumDecode(
          _$ExamCoachPaperVersionLicenceKindEnumEnumMap,
          v,
          unknownValue:
              ExamCoachPaperVersionLicenceKindEnum.unknownDefaultOpenApi,
        ),
      ),
      licenceReference: $checkedConvert(
        'licence_reference',
        (v) => v as String,
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map((e) => ExamCoachQuestion.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      licence: $checkedConvert(
        'licence',
        (v) => v == null
            ? null
            : ExamCoachLicence.fromJson(v as Map<String, dynamic>),
      ),
      revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      groupIds: $checkedConvert(
        'group_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      canEdit: $checkedConvert('can_edit', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {
    'versionId': 'version_id',
    'paperYear': 'paper_year',
    'paperCode': 'paper_code',
    'licenceKind': 'licence_kind',
    'licenceReference': 'licence_reference',
    'groupIds': 'group_ids',
    'canEdit': 'can_edit',
  },
);

Map<String, dynamic> _$ExamCoachPaperVersionToJson(
  ExamCoachPaperVersion instance,
) => <String, dynamic>{
  'id': instance.id,
  'version_id': instance.versionId,
  'number': instance.number,
  'title': instance.title,
  'framework': instance.framework,
  'paper_year': instance.paperYear,
  'paper_code': instance.paperCode,
  'state': _$ExamCoachPaperVersionStateEnumEnumMap[instance.state]!,
  'licence_kind':
      _$ExamCoachPaperVersionLicenceKindEnumEnumMap[instance.licenceKind]!,
  'licence_reference': instance.licenceReference,
  'questions': instance.questions.map((e) => e.toJson()).toList(),
  'licence': ?instance.licence?.toJson(),
  'revision': ?instance.revision,
  'group_ids': ?instance.groupIds,
  'can_edit': ?instance.canEdit,
};

const _$ExamCoachPaperVersionStateEnumEnumMap = {
  ExamCoachPaperVersionStateEnum.draft: 'draft',
  ExamCoachPaperVersionStateEnum.published: 'published',
  ExamCoachPaperVersionStateEnum.withdrawn: 'withdrawn',
  ExamCoachPaperVersionStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ExamCoachPaperVersionLicenceKindEnumEnumMap = {
  ExamCoachPaperVersionLicenceKindEnum.schoolOwned: 'school_owned',
  ExamCoachPaperVersionLicenceKindEnum.openLicence: 'open_licence',
  ExamCoachPaperVersionLicenceKindEnum.permission: 'permission',
  ExamCoachPaperVersionLicenceKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
