// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionResultResponse _$PeerSessionResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PeerSessionResultResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PeerSessionResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PeerSessionResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PeerSessionResultResponseToJson(
  PeerSessionResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
