// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_option_count.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationOptionCount _$ConsultationOptionCountFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationOptionCount', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['option_id', 'count']);
  final val = ConsultationOptionCount(
    optionId: $checkedConvert('option_id', (v) => (v as num).toInt()),
    count: $checkedConvert('count', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'optionId': 'option_id'});

Map<String, dynamic> _$ConsultationOptionCountToJson(
  ConsultationOptionCount instance,
) => <String, dynamic>{'option_id': instance.optionId, 'count': instance.count};
