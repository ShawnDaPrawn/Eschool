// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_lookup.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterLookup _$VendorCounterLookupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'VendorCounterLookup',
      json,
      ($checkedConvert) {
        final val = VendorCounterLookup(
          orderNumber: $checkedConvert('order_number', (v) => v as String?),
          qrToken: $checkedConvert('qr_token', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {'orderNumber': 'order_number', 'qrToken': 'qr_token'},
    );

Map<String, dynamic> _$VendorCounterLookupToJson(
  VendorCounterLookup instance,
) => <String, dynamic>{
  'order_number': ?instance.orderNumber,
  'qr_token': ?instance.qrToken,
};
