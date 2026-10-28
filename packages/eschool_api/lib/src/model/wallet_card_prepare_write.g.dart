// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_card_prepare_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCardPrepareWrite _$WalletCardPrepareWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletCardPrepareWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'card_id', 'picc', 'mac', 'lines'],
  );
  final val = WalletCardPrepareWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    cardId: $checkedConvert('card_id', (v) => v as String),
    picc: $checkedConvert('picc', (v) => v as String),
    mac: $checkedConvert('mac', (v) => v as String),
    lines: $checkedConvert(
      'lines',
      (v) => (v as List<dynamic>)
          .map(
            (e) => WalletCardPrepareWriteLinesInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'cardId': 'card_id'});

Map<String, dynamic> _$WalletCardPrepareWriteToJson(
  WalletCardPrepareWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'card_id': instance.cardId,
  'picc': instance.picc,
  'mac': instance.mac,
  'lines': instance.lines.map((e) => e.toJson()).toList(),
};
