// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_award.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportAward _$LearnerPassportAwardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerPassportAward',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'title',
        'criteria',
        'presented_on',
        'event',
        'outside_organisation',
        'kind',
      ],
    );
    final val = LearnerPassportAward(
      title: $checkedConvert('title', (v) => v as String),
      criteria: $checkedConvert('criteria', (v) => v as String),
      presentedOn: $checkedConvert(
        'presented_on',
        (v) => DateTime.parse(v as String),
      ),
      event: $checkedConvert('event', (v) => v as String?),
      outsideOrganisation: $checkedConvert(
        'outside_organisation',
        (v) => v as String?,
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$LearnerPassportAwardKindEnumEnumMap,
          v,
          unknownValue: LearnerPassportAwardKindEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'presentedOn': 'presented_on',
    'outsideOrganisation': 'outside_organisation',
  },
);

Map<String, dynamic> _$LearnerPassportAwardToJson(
  LearnerPassportAward instance,
) => <String, dynamic>{
  'title': instance.title,
  'criteria': instance.criteria,
  'presented_on': instance.presentedOn.toIso8601String(),
  'event': instance.event,
  'outside_organisation': instance.outsideOrganisation,
  'kind': _$LearnerPassportAwardKindEnumEnumMap[instance.kind]!,
};

const _$LearnerPassportAwardKindEnumEnumMap = {
  LearnerPassportAwardKindEnum.catalogue: 'catalogue',
  LearnerPassportAwardKindEnum.outside: 'outside',
  LearnerPassportAwardKindEnum.badge: 'badge',
  LearnerPassportAwardKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
