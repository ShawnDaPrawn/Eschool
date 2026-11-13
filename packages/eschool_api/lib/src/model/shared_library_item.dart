//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_item_summary_licence.dart';
import 'package:eschool_api/src/model/shared_library_rating_summary.dart';
import 'package:eschool_api/src/model/shared_library_item_comments_inner.dart';
import 'package:eschool_api/src/model/shared_library_item_question.dart';
import 'package:eschool_api/src/model/shared_library_item_my_rating.dart';
import 'package:eschool_api/src/model/shared_library_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItem {
  /// Returns a new [SharedLibraryItem] instance.
  SharedLibraryItem({
    required this.id,

    required this.title,

    required this.topic,

    required this.attribution,

    required this.licence,

    required this.rating,

    required this.copied,

    required this.versionId,

    required this.question,

    required this.estimatedSeconds,

    required this.comments,

    required this.myRating,

    required this.canRate,

    required this.ownSchool,

    required this.copyable,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'topic', required: true, includeIfNull: false)
  final SharedLibraryTopic topic;

  @JsonKey(name: r'attribution', required: true, includeIfNull: false)
  final String attribution;

  @JsonKey(name: r'licence', required: true, includeIfNull: false)
  final SharedLibraryItemSummaryLicence licence;

  @JsonKey(name: r'rating', required: true, includeIfNull: true)
  final SharedLibraryRatingSummary? rating;

  @JsonKey(name: r'copied', required: true, includeIfNull: false)
  final bool copied;

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final SharedLibraryItemQuestion question;

  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'comments', required: true, includeIfNull: false)
  final List<SharedLibraryItemCommentsInner> comments;

  @JsonKey(name: r'my_rating', required: true, includeIfNull: true)
  final SharedLibraryItemMyRating? myRating;

  @JsonKey(name: r'can_rate', required: true, includeIfNull: false)
  final bool canRate;

  @JsonKey(name: r'own_school', required: true, includeIfNull: false)
  final bool ownSchool;

  @JsonKey(name: r'copyable', required: true, includeIfNull: false)
  final bool copyable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItem &&
          other.id == id &&
          other.title == title &&
          other.topic == topic &&
          other.attribution == attribution &&
          other.licence == licence &&
          other.rating == rating &&
          other.copied == copied &&
          other.versionId == versionId &&
          other.question == question &&
          other.estimatedSeconds == estimatedSeconds &&
          other.comments == comments &&
          other.myRating == myRating &&
          other.canRate == canRate &&
          other.ownSchool == ownSchool &&
          other.copyable == copyable;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      topic.hashCode +
      attribution.hashCode +
      licence.hashCode +
      (rating == null ? 0 : rating.hashCode) +
      copied.hashCode +
      versionId.hashCode +
      question.hashCode +
      estimatedSeconds.hashCode +
      comments.hashCode +
      (myRating == null ? 0 : myRating.hashCode) +
      canRate.hashCode +
      ownSchool.hashCode +
      copyable.hashCode;

  factory SharedLibraryItem.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
