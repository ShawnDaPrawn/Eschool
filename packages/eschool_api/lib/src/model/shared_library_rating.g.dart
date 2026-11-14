// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_rating.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryRating _$SharedLibraryRatingFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SharedLibraryRating', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['stars', 'comment', 'counted']);
      final val = SharedLibraryRating(
        stars: $checkedConvert('stars', (v) => (v as num).toInt()),
        comment: $checkedConvert('comment', (v) => v as String?),
        counted: $checkedConvert('counted', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$SharedLibraryRatingToJson(
  SharedLibraryRating instance,
) => <String, dynamic>{
  'stars': instance.stars,
  'comment': instance.comment,
  'counted': instance.counted,
};
