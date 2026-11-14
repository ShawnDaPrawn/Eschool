//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_catalogue_filters.dart';
import 'package:eschool_api/src/model/shared_library_item_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_catalogue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryCatalogue {
  /// Returns a new [SharedLibraryCatalogue] instance.
  SharedLibraryCatalogue({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,

    required this.filters,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SharedLibraryItemSummary> items;

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'filters', required: true, includeIfNull: false)
  final SharedLibraryCatalogueFilters filters;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryCatalogue &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.filters == filters;

  @override
  int get hashCode =>
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      total.hashCode +
      filters.hashCode;

  factory SharedLibraryCatalogue.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryCatalogueFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryCatalogueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
