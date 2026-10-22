//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pagination.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Pagination {
  /// Returns a new [Pagination] instance.
  Pagination({
    required this.page,

    required this.perPage,

    required this.total,

    required this.lastPage,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  // maximum: 100
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Pagination &&
          other.page == page &&
          other.perPage == perPage &&
          other.total == total &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      page.hashCode + perPage.hashCode + total.hashCode + lastPage.hashCode;

  factory Pagination.fromJson(Map<String, dynamic> json) =>
      _$PaginationFromJson(json);

  Map<String, dynamic> toJson() => _$PaginationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
