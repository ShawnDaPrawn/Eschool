// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_recipients_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsRecipientsResponseData _$StrengthsRecipientsResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsRecipientsResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['recipients']);
  final val = StrengthsRecipientsResponseData(
    recipients: $checkedConvert(
      'recipients',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsRecipient.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$StrengthsRecipientsResponseDataToJson(
  StrengthsRecipientsResponseData instance,
) => <String, dynamic>{
  'recipients': instance.recipients.map((e) => e.toJson()).toList(),
};
