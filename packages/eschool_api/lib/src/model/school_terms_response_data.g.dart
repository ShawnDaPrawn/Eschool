// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_terms_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTermsResponseData _$SchoolTermsResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolTermsResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['session_year_id', 'terms']);
  final val = SchoolTermsResponseData(
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    terms: $checkedConvert(
      'terms',
      (v) => (v as List<dynamic>)
          .map((e) => SchoolTerm.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$SchoolTermsResponseDataToJson(
  SchoolTermsResponseData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'terms': instance.terms.map((e) => e.toJson()).toList(),
};
