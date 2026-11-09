// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_withdrawn.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractWithdrawn _$ReenrolmentContractWithdrawnFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractWithdrawn', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'state', 'revision']);
  final val = ReenrolmentContractWithdrawn(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    state: $checkedConvert('state', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentContractWithdrawnToJson(
  ReenrolmentContractWithdrawn instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': instance.state,
  'revision': instance.revision,
};
