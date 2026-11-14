//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_item_summary_licence.dart';
import 'package:eschool_api/src/model/shared_library_rating_summary.dart';
import 'package:eschool_api/src/model/shared_library_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItemSummary {
  /// Returns a new [SharedLibraryItemSummary] instance.
  SharedLibraryItemSummary({
    required this.id,

    required this.title,

    required this.topic,

    required this.attribution,

    required this.licence,

    required this.rating,

    required this.copied,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItemSummary &&
          other.id == id &&
          other.title == title &&
          other.topic == topic &&
          other.attribution == attribution &&
          other.licence == licence &&
          other.rating == rating &&
          other.copied == copied;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      topic.hashCode +
      attribution.hashCode +
      licence.hashCode +
      (rating == null ? 0 : rating.hashCode) +
      copied.hashCode;

  factory SharedLibraryItemSummary.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryItemSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
