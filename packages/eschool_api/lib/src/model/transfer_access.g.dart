// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferAccess _$TransferAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TransferAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'staff',
            'guardian',
            'can_write',
            'consent_version',
            'today',
          ],
        );
        final val = TransferAccess(
          staff: $checkedConvert('staff', (v) => v as bool),
          guardian: $checkedConvert('guardian', (v) => v as bool),
          canWrite: $checkedConvert('can_write', (v) => v as bool),
          consentVersion: $checkedConvert(
            'consent_version',
            (v) => v as String,
          ),
          today: $checkedConvert('today', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'canWrite': 'can_write',
        'consentVersion': 'consent_version',
      },
    );

Map<String, dynamic> _$TransferAccessToJson(TransferAccess instance) =>
    <String, dynamic>{
      'staff': instance.staff,
      'guardian': instance.guardian,
      'can_write': instance.canWrite,
      'consent_version': instance.consentVersion,
      'today': instance.today,
    };
