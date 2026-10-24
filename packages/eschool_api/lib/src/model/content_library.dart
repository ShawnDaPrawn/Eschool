//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_library_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_library.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentLibrary {
  /// Returns a new [ContentLibrary] instance.
  ContentLibrary({
    required this.learnerId,

    required this.page,

    required this.lastPage,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ContentLibraryItem> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentLibrary &&
          other.learnerId == learnerId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode =>
      learnerId.hashCode + page.hashCode + lastPage.hashCode + items.hashCode;

  factory ContentLibrary.fromJson(Map<String, dynamic> json) =>
      _$ContentLibraryFromJson(json);

  Map<String, dynamic> toJson() => _$ContentLibraryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
