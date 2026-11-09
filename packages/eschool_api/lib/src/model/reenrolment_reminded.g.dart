// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_reminded.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentReminded _$ReenrolmentRemindedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReenrolmentReminded', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['contract_id', 'learners', 'notices', 'more'],
      );
      final val = ReenrolmentReminded(
        contractId: $checkedConvert('contract_id', (v) => (v as num).toInt()),
        learners: $checkedConvert('learners', (v) => (v as num).toInt()),
        notices: $checkedConvert('notices', (v) => (v as num).toInt()),
        more: $checkedConvert('more', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'contractId': 'contract_id'});

Map<String, dynamic> _$ReenrolmentRemindedToJson(
  ReenrolmentReminded instance,
) => <String, dynamic>{
  'contract_id': instance.contractId,
  'learners': instance.learners,
  'notices': instance.notices,
  'more': instance.more,
};
