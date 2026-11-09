// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_primary_identifier.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPrimaryIdentifier _$LearnerPrimaryIdentifierFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPrimaryIdentifier', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['type', 'value']);
  final val = LearnerPrimaryIdentifier(
    type: $checkedConvert(
      'type',
      (v) => $enumDecode(
        _$LearnerPrimaryIdentifierTypeEnumEnumMap,
        v,
        unknownValue: LearnerPrimaryIdentifierTypeEnum.unknownDefaultOpenApi,
      ),
    ),
    value: $checkedConvert('value', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$LearnerPrimaryIdentifierToJson(
  LearnerPrimaryIdentifier instance,
) => <String, dynamic>{
  'type': _$LearnerPrimaryIdentifierTypeEnumEnumMap[instance.type]!,
  'value': instance.value,
};

const _$LearnerPrimaryIdentifierTypeEnumEnumMap = {
  LearnerPrimaryIdentifierTypeEnum.lurits: 'lurits',
  LearnerPrimaryIdentifierTypeEnum.navigateed: 'navigateed',
  LearnerPrimaryIdentifierTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
