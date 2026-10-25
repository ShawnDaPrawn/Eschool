// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_mine.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverMine _$CoverMineFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CoverMine', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['occurrences']);
      final val = CoverMine(
        occurrences: $checkedConvert(
          'occurrences',
          (v) => (v as List<dynamic>)
              .map((e) => CoverOccurrence.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CoverMineToJson(CoverMine instance) => <String, dynamic>{
  'occurrences': instance.occurrences.map((e) => e.toJson()).toList(),
};
