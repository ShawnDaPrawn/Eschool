// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_assignments_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAssignmentsResponseData _$AcademicAssignmentsResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAssignmentsResponseData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['assignments', 'current_page', 'last_page', 'total'],
  );
  final val = AcademicAssignmentsResponseData(
    assignments: $checkedConvert(
      'assignments',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicAssignment.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'lastPage': 'last_page'});

Map<String, dynamic> _$AcademicAssignmentsResponseDataToJson(
  AcademicAssignmentsResponseData instance,
) => <String, dynamic>{
  'assignments': instance.assignments.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'last_page': instance.lastPage,
  'total': instance.total,
};
