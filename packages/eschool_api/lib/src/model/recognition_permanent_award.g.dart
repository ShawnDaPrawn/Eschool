// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_permanent_award.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPermanentAward _$RecognitionPermanentAwardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionPermanentAward',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'session_year_id',
        'title',
        'criteria',
        'presented_on',
        'event',
        'outside_organisation',
        'source_kind',
      ],
    );
    final val = RecognitionPermanentAward(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      criteria: $checkedConvert('criteria', (v) => v as String),
      presentedOn: $checkedConvert('presented_on', (v) => v as String),
      event: $checkedConvert('event', (v) => v as String?),
      outsideOrganisation: $checkedConvert(
        'outside_organisation',
        (v) => v as String?,
      ),
      sourceKind: $checkedConvert('source_kind', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'presentedOn': 'presented_on',
    'outsideOrganisation': 'outside_organisation',
    'sourceKind': 'source_kind',
  },
);

Map<String, dynamic> _$RecognitionPermanentAwardToJson(
  RecognitionPermanentAward instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year_id': instance.sessionYearId,
  'title': instance.title,
  'criteria': instance.criteria,
  'presented_on': instance.presentedOn,
  'event': instance.event,
  'outside_organisation': instance.outsideOrganisation,
  'source_kind': instance.sourceKind,
};
