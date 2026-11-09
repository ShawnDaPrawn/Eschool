// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_practice_test_blueprints_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListPracticeTestBlueprintsResponse _$ListPracticeTestBlueprintsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListPracticeTestBlueprintsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListPracticeTestBlueprintsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeTestBlueprintPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListPracticeTestBlueprintsResponseToJson(
  ListPracticeTestBlueprintsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
