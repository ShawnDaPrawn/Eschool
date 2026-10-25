// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_authorize_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LiveAuthorizeRequest _$LiveAuthorizeRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LiveAuthorizeRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['socket_id', 'channel_name']);
  final val = LiveAuthorizeRequest(
    socketId: $checkedConvert('socket_id', (v) => v as String),
    channelName: $checkedConvert('channel_name', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'socketId': 'socket_id', 'channelName': 'channel_name'});

Map<String, dynamic> _$LiveAuthorizeRequestToJson(
  LiveAuthorizeRequest instance,
) => <String, dynamic>{
  'socket_id': instance.socketId,
  'channel_name': instance.channelName,
};
