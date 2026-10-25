// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_vote_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationVoteReceipt _$ConsultationVoteReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationVoteReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['participated']);
  final val = ConsultationVoteReceipt(
    participated: $checkedConvert('participated', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$ConsultationVoteReceiptToJson(
  ConsultationVoteReceipt instance,
) => <String, dynamic>{'participated': instance.participated};
