// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftPage _$TeachingDraftPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TeachingDraftPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'group_id',
            'items',
            'page',
            'last_page',
          ],
        );
        final val = TeachingDraftPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      TeachingDraftSummary.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'groupId': 'group_id',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$TeachingDraftPageToJson(TeachingDraftPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'group_id': instance.groupId,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
