// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_mine_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverMineResponse _$CoverMineResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CoverMineResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = CoverMineResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => CoverMine.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CoverMineResponseToJson(CoverMineResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'code': instance.code,
      'data': instance.data.toJson(),
    };
