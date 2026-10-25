// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_features.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorFeatures _$FacilitatorFeaturesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FacilitatorFeatures', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['groups', 'messages', 'counselling'],
      );
      final val = FacilitatorFeatures(
        groups: $checkedConvert('groups', (v) => v as bool),
        messages: $checkedConvert('messages', (v) => v as bool),
        counselling: $checkedConvert('counselling', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$FacilitatorFeaturesToJson(
  FacilitatorFeatures instance,
) => <String, dynamic>{
  'groups': instance.groups,
  'messages': instance.messages,
  'counselling': instance.counselling,
};
