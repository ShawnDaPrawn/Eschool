//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_choice.dart';
import 'package:eschool_api/src/model/pathway_term_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayConfiguration {
  /// Returns a new [PathwayConfiguration] instance.
  PathwayConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.writable,

    required this.canManage,

    required this.children,

    required this.subjects,

    required this.years,

    required this.terms,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<PathwayChoice> children;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<PathwayChoice> subjects;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<PathwayChoice> years;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<PathwayTermChoice> terms;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.writable == writable &&
          other.canManage == canManage &&
          other.children == children &&
          other.subjects == subjects &&
          other.years == years &&
          other.terms == terms;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      writable.hashCode +
      canManage.hashCode +
      children.hashCode +
      subjects.hashCode +
      years.hashCode +
      terms.hashCode;

  factory PathwayConfiguration.fromJson(Map<String, dynamic> json) =>
      _$PathwayConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
