// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item_comments_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItemCommentsInner _$SharedLibraryItemCommentsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryItemCommentsInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['stars', 'comment']);
  final val = SharedLibraryItemCommentsInner(
    stars: $checkedConvert('stars', (v) => (v as num).toInt()),
    comment: $checkedConvert('comment', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryItemCommentsInnerToJson(
  SharedLibraryItemCommentsInner instance,
) => <String, dynamic>{'stars': instance.stars, 'comment': instance.comment};
