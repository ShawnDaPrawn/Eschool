// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_match_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerMatchPageResponse _$PeerMatchPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PeerMatchPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PeerMatchPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PeerMatchPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PeerMatchPageResponseToJson(
  PeerMatchPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
