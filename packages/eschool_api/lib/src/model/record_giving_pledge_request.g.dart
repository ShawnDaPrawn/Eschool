// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'record_giving_pledge_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecordGivingPledgeRequest _$RecordGivingPledgeRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecordGivingPledgeRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'donor_name',
        'amount_cents',
        'pledged_on',
      ],
    );
    final val = RecordGivingPledgeRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      donorName: $checkedConvert('donor_name', (v) => v as String),
      donorEmail: $checkedConvert('donor_email', (v) => v as String?),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      pledgedOn: $checkedConvert(
        'pledged_on',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'donorName': 'donor_name',
    'donorEmail': 'donor_email',
    'amountCents': 'amount_cents',
    'pledgedOn': 'pledged_on',
  },
);

Map<String, dynamic> _$RecordGivingPledgeRequestToJson(
  RecordGivingPledgeRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'donor_name': instance.donorName,
  'donor_email': ?instance.donorEmail,
  'amount_cents': instance.amountCents,
  'pledged_on': instance.pledgedOn.toIso8601String(),
};
