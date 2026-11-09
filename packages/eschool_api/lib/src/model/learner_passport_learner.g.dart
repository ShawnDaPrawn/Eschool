// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportLearner _$LearnerPassportLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportLearner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'primary_identifier']);
  final val = LearnerPassportLearner(
    name: $checkedConvert('name', (v) => v as String),
    primaryIdentifier: $checkedConvert(
      'primary_identifier',
      (v) => v == null
          ? null
          : LearnerPrimaryIdentifier.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'primaryIdentifier': 'primary_identifier'});

Map<String, dynamic> _$LearnerPassportLearnerToJson(
  LearnerPassportLearner instance,
) => <String, dynamic>{
  'name': instance.name,
  'primary_identifier': instance.primaryIdentifier?.toJson(),
};
