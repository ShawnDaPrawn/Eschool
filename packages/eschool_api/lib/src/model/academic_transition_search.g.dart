// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_search.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionSearch _$AcademicTransitionSearchFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionSearch', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'last_page', 'learners']);
  final val = AcademicTransitionSearch(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    learners: $checkedConvert(
      'learners',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicTransitionLearnerChoice.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$AcademicTransitionSearchToJson(
  AcademicTransitionSearch instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
