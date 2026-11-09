// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'record_giving_donation_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecordGivingDonationRequest _$RecordGivingDonationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecordGivingDonationRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'donor_name',
        'amount_cents',
        'received_on',
        'method',
        'source_reference',
        'confirmed',
      ],
    );
    final val = RecordGivingDonationRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      donorName: $checkedConvert('donor_name', (v) => v as String),
      donorEmail: $checkedConvert('donor_email', (v) => v as String?),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      receivedOn: $checkedConvert(
        'received_on',
        (v) => DateTime.parse(v as String),
      ),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$RecordGivingDonationRequestMethodEnumEnumMap,
          v,
          unknownValue:
              RecordGivingDonationRequestMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      pledgeId: $checkedConvert('pledge_id', (v) => (v as num?)?.toInt()),
      confirmed: $checkedConvert(
        'confirmed',
        (v) => RecordGivingDonationRequestConfirmedEnum.fromJson(v),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'donorName': 'donor_name',
    'donorEmail': 'donor_email',
    'amountCents': 'amount_cents',
    'receivedOn': 'received_on',
    'sourceReference': 'source_reference',
    'pledgeId': 'pledge_id',
  },
);

Map<String, dynamic> _$RecordGivingDonationRequestToJson(
  RecordGivingDonationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'donor_name': instance.donorName,
  'donor_email': ?instance.donorEmail,
  'amount_cents': instance.amountCents,
  'received_on': instance.receivedOn.toIso8601String(),
  'method': _$RecordGivingDonationRequestMethodEnumEnumMap[instance.method]!,
  'source_reference': instance.sourceReference,
  'pledge_id': ?instance.pledgeId,
  'confirmed': ?RecordGivingDonationRequestConfirmedEnum.toJsonValue(
    instance.confirmed,
  ),
};

const _$RecordGivingDonationRequestMethodEnumEnumMap = {
  RecordGivingDonationRequestMethodEnum.eft: 'eft',
  RecordGivingDonationRequestMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
