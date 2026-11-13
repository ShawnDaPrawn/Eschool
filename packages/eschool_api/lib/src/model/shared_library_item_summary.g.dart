// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItemSummary _$SharedLibraryItemSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryItemSummary', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'title',
      'topic',
      'attribution',
      'licence',
      'rating',
      'copied',
    ],
  );
  final val = SharedLibraryItemSummary(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    title: $checkedConvert('title', (v) => v as String),
    topic: $checkedConvert(
      'topic',
      (v) => SharedLibraryTopic.fromJson(v as Map<String, dynamic>),
    ),
    attribution: $checkedConvert('attribution', (v) => v as String),
    licence: $checkedConvert(
      'licence',
      (v) =>
          SharedLibraryItemSummaryLicence.fromJson(v as Map<String, dynamic>),
    ),
    rating: $checkedConvert(
      'rating',
      (v) => v == null
          ? null
          : SharedLibraryRatingSummary.fromJson(v as Map<String, dynamic>),
    ),
    copied: $checkedConvert('copied', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryItemSummaryToJson(
  SharedLibraryItemSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'topic': instance.topic.toJson(),
  'attribution': instance.attribution,
  'licence': instance.licence.toJson(),
  'rating': instance.rating?.toJson(),
  'copied': instance.copied,
};
