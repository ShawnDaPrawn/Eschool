//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_session_school.dart';
import 'package:eschool_api/src/model/shared_library_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_catalogue_filters.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryCatalogueFilters {
  /// Returns a new [SharedLibraryCatalogueFilters] instance.
  SharedLibraryCatalogueFilters({
    required this.frameworks,

    required this.subjectCodes,

    required this.grades,

    required this.topics,
  });

  @JsonKey(name: r'frameworks', required: true, includeIfNull: false)
  final List<String> frameworks;

  @JsonKey(name: r'subject_codes', required: true, includeIfNull: false)
  final List<String> subjectCodes;

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<ChildModeSessionSchool> grades;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<SharedLibraryTopic> topics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryCatalogueFilters &&
          other.frameworks == frameworks &&
          other.subjectCodes == subjectCodes &&
          other.grades == grades &&
          other.topics == topics;

  @override
  int get hashCode =>
      frameworks.hashCode +
      subjectCodes.hashCode +
      grades.hashCode +
      topics.hashCode;

  factory SharedLibraryCatalogueFilters.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryCatalogueFiltersFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryCatalogueFiltersToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
