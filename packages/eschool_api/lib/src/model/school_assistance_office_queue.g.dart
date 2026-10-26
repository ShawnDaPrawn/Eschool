// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_office_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceOfficeQueue _$SchoolAssistanceOfficeQueueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceOfficeQueue',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'page',
        'last_page',
        'items',
        'school_id',
        'reader_id',
      ],
    );
    final val = SchoolAssistanceOfficeQueue(
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => SchoolAssistanceOfficeThreadMetadata.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'lastPage': 'last_page',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceOfficeQueueToJson(
  SchoolAssistanceOfficeQueue instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};
