// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_report_channels.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReportChannels _$CommunicationMessageReportChannelsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageReportChannels', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['email', 'push']);
  final val = CommunicationMessageReportChannels(
    email: $checkedConvert(
      'email',
      (v) => CommunicationDeliveryCounts.fromJson(v as Map<String, dynamic>),
    ),
    push: $checkedConvert(
      'push',
      (v) => CommunicationDeliveryCounts.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationMessageReportChannelsToJson(
  CommunicationMessageReportChannels instance,
) => <String, dynamic>{
  'email': instance.email.toJson(),
  'push': instance.push.toJson(),
};
