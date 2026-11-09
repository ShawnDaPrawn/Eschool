// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureListResponse _$FixtureListResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureListResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = FixtureListResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => FixtureList.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$FixtureListResponseToJson(
  FixtureListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
