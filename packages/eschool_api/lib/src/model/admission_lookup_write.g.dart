// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_lookup_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionLookupWrite _$AdmissionLookupWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionLookupWrite', json, ($checkedConvert) {
  final val = AdmissionLookupWrite(
    qrToken: $checkedConvert('qr_token', (v) => v as String?),
    ticketNumber: $checkedConvert('ticket_number', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'qrToken': 'qr_token', 'ticketNumber': 'ticket_number'});

Map<String, dynamic> _$AdmissionLookupWriteToJson(
  AdmissionLookupWrite instance,
) => <String, dynamic>{
  'qr_token': ?instance.qrToken,
  'ticket_number': ?instance.ticketNumber,
};
