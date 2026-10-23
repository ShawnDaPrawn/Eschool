// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_validation_rules.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileValidationRules _$ProfileValidationRulesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProfileValidationRules', json, ($checkedConvert) {
  final val = ProfileValidationRules(
    min: $checkedConvert('min', (v) => (v as num?)?.toInt()),
    max: $checkedConvert('max', (v) => (v as num?)?.toInt()),
    email: $checkedConvert('email', (v) => v as bool?),
    past: $checkedConvert('past', (v) => v as bool?),
  );
  return val;
});

Map<String, dynamic> _$ProfileValidationRulesToJson(
  ProfileValidationRules instance,
) => <String, dynamic>{
  'min': ?instance.min,
  'max': ?instance.max,
  'email': ?instance.email,
  'past': ?instance.past,
};
