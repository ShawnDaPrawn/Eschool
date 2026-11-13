// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_catalogue_filters.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCatalogueFilters _$SharedLibraryCatalogueFiltersFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryCatalogueFilters', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['frameworks', 'subject_codes', 'grades', 'topics'],
  );
  final val = SharedLibraryCatalogueFilters(
    frameworks: $checkedConvert(
      'frameworks',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    subjectCodes: $checkedConvert(
      'subject_codes',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    grades: $checkedConvert(
      'grades',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ChildModeSessionSchool.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    topics: $checkedConvert(
      'topics',
      (v) => (v as List<dynamic>)
          .map((e) => SharedLibraryTopic.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'subjectCodes': 'subject_codes'});

Map<String, dynamic> _$SharedLibraryCatalogueFiltersToJson(
  SharedLibraryCatalogueFilters instance,
) => <String, dynamic>{
  'frameworks': instance.frameworks,
  'subject_codes': instance.subjectCodes,
  'grades': instance.grades.map((e) => e.toJson()).toList(),
  'topics': instance.topics.map((e) => e.toJson()).toList(),
};
