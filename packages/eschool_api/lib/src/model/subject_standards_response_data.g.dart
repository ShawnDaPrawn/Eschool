// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subject_standards_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubjectStandardsResponseData _$SubjectStandardsResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubjectStandardsResponseData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['standards', 'current_page', 'last_page', 'total'],
  );
  final val = SubjectStandardsResponseData(
    standards: $checkedConvert(
      'standards',
      (v) => (v as List<dynamic>)
          .map((e) => SubjectStandard.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'lastPage': 'last_page'});

Map<String, dynamic> _$SubjectStandardsResponseDataToJson(
  SubjectStandardsResponseData instance,
) => <String, dynamic>{
  'standards': instance.standards.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'last_page': instance.lastPage,
  'total': instance.total,
};
