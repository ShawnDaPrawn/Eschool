// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_published.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractPublished _$ReenrolmentContractPublishedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentContractPublished', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'version', 'text_hash', 'revision'],
  );
  final val = ReenrolmentContractPublished(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    version: $checkedConvert('version', (v) => (v as num).toInt()),
    textHash: $checkedConvert('text_hash', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'textHash': 'text_hash'});

Map<String, dynamic> _$ReenrolmentContractPublishedToJson(
  ReenrolmentContractPublished instance,
) => <String, dynamic>{
  'id': instance.id,
  'version': instance.version,
  'text_hash': instance.textHash,
  'revision': instance.revision,
};
