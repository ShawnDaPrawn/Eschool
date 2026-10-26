//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_elective_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayElectiveGroup {
  /// Returns a new [PathwayElectiveGroup] instance.
  PathwayElectiveGroup({
    required this.title,

    required this.choose,

    required this.subjectIds,
  });

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'choose', required: true, includeIfNull: false)
  final int choose;

  @JsonKey(name: r'subject_ids', required: true, includeIfNull: false)
  final List<int> subjectIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayElectiveGroup &&
          other.title == title &&
          other.choose == choose &&
          other.subjectIds == subjectIds;

  @override
  int get hashCode => title.hashCode + choose.hashCode + subjectIds.hashCode;

  factory PathwayElectiveGroup.fromJson(Map<String, dynamic> json) =>
      _$PathwayElectiveGroupFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayElectiveGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
