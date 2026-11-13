// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItem _$SharedLibraryItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryItem',
  json,
  ($checkedConvert) {
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
        'version_id',
        'question',
        'estimated_seconds',
        'comments',
        'my_rating',
        'can_rate',
        'own_school',
        'copyable',
      ],
    );
    final val = SharedLibraryItem(
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
      versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
      question: $checkedConvert(
        'question',
        (v) => SharedLibraryItemQuestion.fromJson(v as Map<String, dynamic>),
      ),
      estimatedSeconds: $checkedConvert(
        'estimated_seconds',
        (v) => (v as num).toInt(),
      ),
      comments: $checkedConvert(
        'comments',
        (v) => (v as List<dynamic>)
            .map(
              (e) => SharedLibraryItemCommentsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      myRating: $checkedConvert(
        'my_rating',
        (v) => v == null
            ? null
            : SharedLibraryItemMyRating.fromJson(v as Map<String, dynamic>),
      ),
      canRate: $checkedConvert('can_rate', (v) => v as bool),
      ownSchool: $checkedConvert('own_school', (v) => v as bool),
      copyable: $checkedConvert('copyable', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'versionId': 'version_id',
    'estimatedSeconds': 'estimated_seconds',
    'myRating': 'my_rating',
    'canRate': 'can_rate',
    'ownSchool': 'own_school',
  },
);

Map<String, dynamic> _$SharedLibraryItemToJson(SharedLibraryItem instance) =>
    <String, dynamic>{
      'id': instance.id,
      'title': instance.title,
      'topic': instance.topic.toJson(),
      'attribution': instance.attribution,
      'licence': instance.licence.toJson(),
      'rating': instance.rating?.toJson(),
      'copied': instance.copied,
      'version_id': instance.versionId,
      'question': instance.question.toJson(),
      'estimated_seconds': instance.estimatedSeconds,
      'comments': instance.comments.map((e) => e.toJson()).toList(),
      'my_rating': instance.myRating?.toJson(),
      'can_rate': instance.canRate,
      'own_school': instance.ownSchool,
      'copyable': instance.copyable,
    };
