//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_link.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_graph_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryGraphWrite {
  /// Returns a new [MasteryGraphWrite] instance.
  MasteryGraphWrite({
    required this.expectedRevision,

    required this.familyMarksEnabled,

    required this.links,
  });

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'family_marks_enabled', required: true, includeIfNull: false)
  final bool familyMarksEnabled;

  @JsonKey(name: r'links', required: true, includeIfNull: false)
  final List<MasteryLink> links;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryGraphWrite &&
          other.expectedRevision == expectedRevision &&
          other.familyMarksEnabled == familyMarksEnabled &&
          other.links == links;

  @override
  int get hashCode =>
      expectedRevision.hashCode + familyMarksEnabled.hashCode + links.hashCode;

  factory MasteryGraphWrite.fromJson(Map<String, dynamic> json) =>
      _$MasteryGraphWriteFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryGraphWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
