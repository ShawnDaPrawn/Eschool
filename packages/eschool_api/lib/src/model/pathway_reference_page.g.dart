// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReferencePage _$PathwayReferencePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayReferencePage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['school_id', 'reader_id', 'items', 'next_page'],
    );
    final val = PathwayReferencePage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => PathwayReference.fromJson(e as Map<String, dynamic>))
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

Map<String, dynamic> _$PathwayReferencePageToJson(
  PathwayReferencePage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_page': instance.nextPage,
};
