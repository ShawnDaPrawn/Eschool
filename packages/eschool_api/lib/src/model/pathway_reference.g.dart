// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReference _$PathwayReferenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PathwayReference',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
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
        final val = PathwayReference(
          id: $checkedConvert('id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$PathwayReferenceKindEnumEnumMap,
              v,
              unknownValue: PathwayReferenceKindEnum.unknownDefaultOpenApi,
            ),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$PathwayReferenceStatusEnumEnumMap,
              v,
              unknownValue: PathwayReferenceStatusEnum.unknownDefaultOpenApi,
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
        'sourceTitle': 'source_title',
        'sourceUrl': 'source_url',
        'sourceYear': 'source_year',
        'reviewedOn': 'reviewed_on',
        'expiresOn': 'expires_on',
      },
    );

Map<String, dynamic> _$PathwayReferenceToJson(PathwayReference instance) =>
    <String, dynamic>{
      'id': instance.id,
      'revision': instance.revision,
      'kind': _$PathwayReferenceKindEnumEnumMap[instance.kind]!,
      'status': _$PathwayReferenceStatusEnumEnumMap[instance.status]!,
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

const _$PathwayReferenceKindEnumEnumMap = {
  PathwayReferenceKindEnum.programme: 'programme',
  PathwayReferenceKindEnum.subjectChoiceGuide: 'subject_choice_guide',
  PathwayReferenceKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PathwayReferenceStatusEnumEnumMap = {
  PathwayReferenceStatusEnum.published: 'published',
  PathwayReferenceStatusEnum.withdrawn: 'withdrawn',
  PathwayReferenceStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
