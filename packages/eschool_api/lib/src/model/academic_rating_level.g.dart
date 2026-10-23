// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_rating_level.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRatingLevel _$AcademicRatingLevelFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicRatingLevel',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'level',
            'name',
            'min_basis_points',
            'max_basis_points',
          ],
        );
        final val = AcademicRatingLevel(
          level: $checkedConvert('level', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          minBasisPoints: $checkedConvert(
            'min_basis_points',
            (v) => (v as num).toInt(),
          ),
          maxBasisPoints: $checkedConvert(
            'max_basis_points',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'minBasisPoints': 'min_basis_points',
        'maxBasisPoints': 'max_basis_points',
      },
    );

Map<String, dynamic> _$AcademicRatingLevelToJson(
  AcademicRatingLevel instance,
) => <String, dynamic>{
  'level': instance.level,
  'name': instance.name,
  'min_basis_points': instance.minBasisPoints,
  'max_basis_points': instance.maxBasisPoints,
};
