// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_quote_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceQuoteWrite _$CommerceQuoteWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommerceQuoteWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['vendor_id', 'wallet_id', 'lines']);
    final val = CommerceQuoteWrite(
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      lines: $checkedConvert(
        'lines',
        (v) => (v as List<dynamic>)
            .map((e) => CommerceCartLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'vendorId': 'vendor_id',
    'walletId': 'wallet_id',
    'learnerId': 'learner_id',
  },
);

Map<String, dynamic> _$CommerceQuoteWriteToJson(CommerceQuoteWrite instance) =>
    <String, dynamic>{
      'vendor_id': instance.vendorId,
      'wallet_id': instance.walletId,
      'learner_id': ?instance.learnerId,
      'lines': instance.lines.map((e) => e.toJson()).toList(),
    };
