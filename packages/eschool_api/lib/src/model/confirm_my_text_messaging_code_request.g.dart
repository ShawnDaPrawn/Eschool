// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'confirm_my_text_messaging_code_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConfirmMyTextMessagingCodeRequest _$ConfirmMyTextMessagingCodeRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConfirmMyTextMessagingCodeRequest', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['code']);
  final val = ConfirmMyTextMessagingCodeRequest(
    code: $checkedConvert('code', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$ConfirmMyTextMessagingCodeRequestToJson(
  ConfirmMyTextMessagingCodeRequest instance,
) => <String, dynamic>{'code': instance.code};
