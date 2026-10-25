//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_definition.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_framework.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineFramework {
  /// Returns a new [DisciplineFramework] instance.
  DisciplineFramework({
    required this.id,

    this.sessionYearId,

    required this.title,

    required this.revision,

    required this.definition,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final DisciplineDefinition definition;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineFramework &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.revision == revision &&
          other.definition == definition;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      revision.hashCode +
      definition.hashCode;

  factory DisciplineFramework.fromJson(Map<String, dynamic> json) =>
      _$DisciplineFrameworkFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineFrameworkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
