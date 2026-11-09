// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureResponse _$FixtureResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = FixtureResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => Fixture.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$FixtureResponseToJson(FixtureResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'code': instance.code,
      'data': instance.data.toJson(),
    };
