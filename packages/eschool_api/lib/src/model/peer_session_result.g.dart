// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionResult _$PeerSessionResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PeerSessionResult', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['school_id', 'reader_id', 'session'],
      );
      final val = PeerSessionResult(
        schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
        readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
        session: $checkedConvert(
          'session',
          (v) => PeerSession.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$PeerSessionResultToJson(PeerSessionResult instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'session': instance.session.toJson(),
    };
