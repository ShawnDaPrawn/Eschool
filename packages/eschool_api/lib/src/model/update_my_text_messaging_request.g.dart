// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_my_text_messaging_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateMyTextMessagingRequest _$UpdateMyTextMessagingRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdateMyTextMessagingRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['type', 'sms', 'whatsapp']);
  final val = UpdateMyTextMessagingRequest(
    type: $checkedConvert('type', (v) => v as String),
    sms: $checkedConvert('sms', (v) => v as bool),
    whatsapp: $checkedConvert('whatsapp', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$UpdateMyTextMessagingRequestToJson(
  UpdateMyTextMessagingRequest instance,
) => <String, dynamic>{
  'type': instance.type,
  'sms': instance.sms,
  'whatsapp': instance.whatsapp,
};
