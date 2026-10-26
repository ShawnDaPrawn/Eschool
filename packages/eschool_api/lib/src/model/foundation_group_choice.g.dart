// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationGroupChoice _$FoundationGroupChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FoundationGroupChoice', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'name', 'eligible', 'revision', 'can_authorize'],
  );
  final val = FoundationGroupChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    eligible: $checkedConvert('eligible', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    canAuthorize: $checkedConvert('can_authorize', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'canAuthorize': 'can_authorize'});

Map<String, dynamic> _$FoundationGroupChoiceToJson(
  FoundationGroupChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'eligible': instance.eligible,
  'revision': instance.revision,
  'can_authorize': instance.canAuthorize,
};
