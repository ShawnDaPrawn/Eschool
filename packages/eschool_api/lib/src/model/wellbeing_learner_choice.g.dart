// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_learner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingLearnerChoice _$WellbeingLearnerChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingLearnerChoice',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['id', 'name', 'admission_no']);
    final val = WellbeingLearnerChoice(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      admissionNo: $checkedConvert('admission_no', (v) => v as String),
      primaryIdentifier: $checkedConvert(
        'primary_identifier',
        (v) => v == null
            ? null
            : LearnerPrimaryIdentifier.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'admissionNo': 'admission_no',
    'primaryIdentifier': 'primary_identifier',
  },
);

Map<String, dynamic> _$WellbeingLearnerChoiceToJson(
  WellbeingLearnerChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'admission_no': instance.admissionNo,
  'primary_identifier': ?instance.primaryIdentifier?.toJson(),
};
