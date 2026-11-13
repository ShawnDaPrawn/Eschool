// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item_my_rating.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItemMyRating _$SharedLibraryItemMyRatingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryItemMyRating', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['stars', 'comment']);
  final val = SharedLibraryItemMyRating(
    stars: $checkedConvert('stars', (v) => (v as num).toInt()),
    comment: $checkedConvert('comment', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryItemMyRatingToJson(
  SharedLibraryItemMyRating instance,
) => <String, dynamic>{'stars': instance.stars, 'comment': instance.comment};
