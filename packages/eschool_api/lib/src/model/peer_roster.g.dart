// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_roster.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerRoster _$PeerRosterFromJson(Map<String, dynamic> json) => $checkedCreate(
  'PeerRoster',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'teaching_group_id',
        'items',
      ],
    );
    final val = PeerRoster(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => PeerChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'teachingGroupId': 'teaching_group_id',
  },
);

Map<String, dynamic> _$PeerRosterToJson(PeerRoster instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'teaching_group_id': instance.teachingGroupId,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
