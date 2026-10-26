// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionPage _$PeerSessionPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PeerSessionPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['school_id', 'reader_id', 'items', 'next_page'],
        );
        final val = PeerSessionPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map((e) => PeerSession.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'nextPage': 'next_page',
      },
    );

Map<String, dynamic> _$PeerSessionPageToJson(PeerSessionPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_page': instance.nextPage,
    };
