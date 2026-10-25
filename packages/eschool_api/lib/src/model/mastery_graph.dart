//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_settings.dart';
import 'package:eschool_api/src/model/mastery_node.dart';
import 'package:eschool_api/src/model/mastery_link.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_graph.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryGraph {
  /// Returns a new [MasteryGraph] instance.
  MasteryGraph({
    required this.subjectId,

    required this.settings,

    required this.nodes,

    required this.links,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final MasterySettings settings;

  @JsonKey(name: r'nodes', required: true, includeIfNull: false)
  final List<MasteryNode> nodes;

  @JsonKey(name: r'links', required: true, includeIfNull: false)
  final List<MasteryLink> links;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryGraph &&
          other.subjectId == subjectId &&
          other.settings == settings &&
          other.nodes == nodes &&
          other.links == links;

  @override
  int get hashCode =>
      subjectId.hashCode + settings.hashCode + nodes.hashCode + links.hashCode;

  factory MasteryGraph.fromJson(Map<String, dynamic> json) =>
      _$MasteryGraphFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryGraphToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
