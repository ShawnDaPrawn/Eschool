// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_sign.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentSign _$FamilyConsentSignFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyConsentSign',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'fingerprint',
            'decision',
            'acknowledged',
            'signed_name',
          ],
        );
        final val = FamilyConsentSign(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          decision: $checkedConvert('decision', (v) => v as String),
          acknowledged: $checkedConvert('acknowledged', (v) => v as bool),
          signedName: $checkedConvert('signed_name', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'signedName': 'signed_name',
      },
    );

Map<String, dynamic> _$FamilyConsentSignToJson(FamilyConsentSign instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'fingerprint': instance.fingerprint,
      'decision': instance.decision,
      'acknowledged': instance.acknowledged,
      'signed_name': instance.signedName,
    };
