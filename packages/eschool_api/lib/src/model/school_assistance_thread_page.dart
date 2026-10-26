//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_thread_metadata.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThreadPage {
  /// Returns a new [SchoolAssistanceThreadPage] instance.
  SchoolAssistanceThreadPage({
    required this.page,

    required this.lastPage,

    required this.items,

    required this.schoolId,

    required this.readerId,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SchoolAssistanceThreadMetadata> items;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThreadPage &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      page.hashCode +
      lastPage.hashCode +
      items.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceThreadPage.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceThreadPageFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceThreadPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
