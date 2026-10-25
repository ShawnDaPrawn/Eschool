// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_cancel.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCancel _$FamilyConsentCancelFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyConsentCancel', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
      final val = FamilyConsentCancel(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$FamilyConsentCancelToJson(
  FamilyConsentCancel instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
