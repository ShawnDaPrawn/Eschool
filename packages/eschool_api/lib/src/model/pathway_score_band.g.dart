// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_score_band.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayScoreBand _$PathwayScoreBandFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PathwayScoreBand', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['minimum_bp', 'points']);
      final val = PathwayScoreBand(
        minimumBp: $checkedConvert('minimum_bp', (v) => (v as num).toInt()),
        points: $checkedConvert('points', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'minimumBp': 'minimum_bp'});

Map<String, dynamic> _$PathwayScoreBandToJson(PathwayScoreBand instance) =>
    <String, dynamic>{
      'minimum_bp': instance.minimumBp,
      'points': instance.points,
    };
