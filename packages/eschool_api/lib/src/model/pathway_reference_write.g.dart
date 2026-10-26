// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReferenceWrite _$PathwayReferenceWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayReferenceWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'kind',
        'status',
        'title',
        'institution',
        'source_title',
        'source_url',
        'source_year',
        'reviewed_on',
        'expires_on',
        'guidance',
        'rules',
      ],
    );
    final val = PathwayReferenceWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PathwayReferenceWriteKindEnumEnumMap,
          v,
          unknownValue: PathwayReferenceWriteKindEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$PathwayReferenceWriteStatusEnumEnumMap,
          v,
          unknownValue: PathwayReferenceWriteStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      institution: $checkedConvert('institution', (v) => v as String?),
      sourceTitle: $checkedConvert('source_title', (v) => v as String),
      sourceUrl: $checkedConvert('source_url', (v) => v as String),
      sourceYear: $checkedConvert('source_year', (v) => (v as num).toInt()),
      reviewedOn: $checkedConvert('reviewed_on', (v) => v as String),
      expiresOn: $checkedConvert('expires_on', (v) => v as String),
      guidance: $checkedConvert('guidance', (v) => v as String),
      rules: $checkedConvert(
        'rules',
        (v) => PathwayRules.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sourceTitle': 'source_title',
    'sourceUrl': 'source_url',
    'sourceYear': 'source_year',
    'reviewedOn': 'reviewed_on',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$PathwayReferenceWriteToJson(
  PathwayReferenceWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'kind': _$PathwayReferenceWriteKindEnumEnumMap[instance.kind]!,
  'status': _$PathwayReferenceWriteStatusEnumEnumMap[instance.status]!,
  'title': instance.title,
  'institution': instance.institution,
  'source_title': instance.sourceTitle,
  'source_url': instance.sourceUrl,
  'source_year': instance.sourceYear,
  'reviewed_on': instance.reviewedOn,
  'expires_on': instance.expiresOn,
  'guidance': instance.guidance,
  'rules': instance.rules.toJson(),
};

const _$PathwayReferenceWriteKindEnumEnumMap = {
  PathwayReferenceWriteKindEnum.programme: 'programme',
  PathwayReferenceWriteKindEnum.subjectChoiceGuide: 'subject_choice_guide',
  PathwayReferenceWriteKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PathwayReferenceWriteStatusEnumEnumMap = {
  PathwayReferenceWriteStatusEnum.published: 'published',
  PathwayReferenceWriteStatusEnum.withdrawn: 'withdrawn',
  PathwayReferenceWriteStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
