// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_school_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSchoolListResponse _$FixtureSchoolListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FixtureSchoolListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = FixtureSchoolListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => FixtureSchoolList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$FixtureSchoolListResponseToJson(
  FixtureSchoolListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
