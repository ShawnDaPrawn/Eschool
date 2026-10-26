// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_source_locator.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceSourceLocator _$SchoolAssistanceSourceLocatorFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceSourceLocator', json, ($checkedConvert) {
  final val = SchoolAssistanceSourceLocator(
    id: $checkedConvert('id', (v) => v as String?),
    year: $checkedConvert('year', (v) => (v as num?)?.toInt()),
    termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
    onDate: $checkedConvert('on_date', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'termId': 'term_id', 'onDate': 'on_date'});

Map<String, dynamic> _$SchoolAssistanceSourceLocatorToJson(
  SchoolAssistanceSourceLocator instance,
) => <String, dynamic>{
  'id': ?instance.id,
  'year': ?instance.year,
  'term_id': ?instance.termId,
  'on_date': ?instance.onDate,
};
