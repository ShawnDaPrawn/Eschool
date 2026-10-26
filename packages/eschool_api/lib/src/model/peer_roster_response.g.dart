// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_roster_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerRosterResponse _$PeerRosterResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PeerRosterResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PeerRosterResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => PeerRoster.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$PeerRosterResponseToJson(PeerRosterResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code,
    };
