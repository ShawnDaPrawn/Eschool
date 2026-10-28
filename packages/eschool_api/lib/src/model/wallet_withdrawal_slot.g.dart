// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_slot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalSlot _$WalletWithdrawalSlotFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletWithdrawalSlot',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'starts_at',
        'ends_at',
        'label',
        'available_places',
      ],
    );
    final val = WalletWithdrawalSlot(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      label: $checkedConvert('label', (v) => v as String),
      availablePlaces: $checkedConvert(
        'available_places',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'availablePlaces': 'available_places',
  },
);

Map<String, dynamic> _$WalletWithdrawalSlotToJson(
  WalletWithdrawalSlot instance,
) => <String, dynamic>{
  'id': instance.id,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
  'label': instance.label,
  'available_places': instance.availablePlaces,
};
