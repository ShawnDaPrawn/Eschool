// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_place_learners_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredPlaceLearnersInner _$SponsoredPlaceLearnersInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SponsoredPlaceLearnersInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'name', 'added_on']);
  final val = SponsoredPlaceLearnersInner(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    addedOn: $checkedConvert(
      'added_on',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'addedOn': 'added_on'});

Map<String, dynamic> _$SponsoredPlaceLearnersInnerToJson(
  SponsoredPlaceLearnersInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'added_on': instance.addedOn?.toIso8601String(),
};
