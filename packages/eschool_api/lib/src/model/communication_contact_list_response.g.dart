// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_contact_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationContactListResponse _$CommunicationContactListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationContactListResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationContactListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationContactList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationContactListResponseToJson(
  CommunicationContactListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
