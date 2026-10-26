// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_match_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerMatchPage _$PeerMatchPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PeerMatchPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'teaching_group_id',
            'learner_id',
            'items',
          ],
        );
        final val = PeerMatchPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          teachingGroupId: $checkedConvert(
            'teaching_group_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map((e) => PeerMatch.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'teachingGroupId': 'teaching_group_id',
        'learnerId': 'learner_id',
      },
    );

Map<String, dynamic> _$PeerMatchPageToJson(PeerMatchPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'teaching_group_id': instance.teachingGroupId,
      'learner_id': instance.learnerId,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
