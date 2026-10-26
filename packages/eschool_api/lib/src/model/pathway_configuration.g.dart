// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayConfiguration _$PathwayConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'writable',
        'can_manage',
        'children',
        'subjects',
        'years',
        'terms',
      ],
    );
    final val = PathwayConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      writable: $checkedConvert('writable', (v) => v as bool),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      children: $checkedConvert(
        'children',
        (v) => (v as List<dynamic>)
            .map((e) => PathwayChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      subjects: $checkedConvert(
        'subjects',
        (v) => (v as List<dynamic>)
            .map((e) => PathwayChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map((e) => PathwayChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      terms: $checkedConvert(
        'terms',
        (v) => (v as List<dynamic>)
            .map((e) => PathwayTermChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'canManage': 'can_manage',
  },
);

Map<String, dynamic> _$PathwayConfigurationToJson(
  PathwayConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'writable': instance.writable,
  'can_manage': instance.canManage,
  'children': instance.children.map((e) => e.toJson()).toList(),
  'subjects': instance.subjects.map((e) => e.toJson()).toList(),
  'years': instance.years.map((e) => e.toJson()).toList(),
  'terms': instance.terms.map((e) => e.toJson()).toList(),
};
