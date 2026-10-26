//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_mark.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayMark {
  /// Returns a new [PathwayMark] instance.
  PathwayMark({required this.subjectId, required this.percentageBp});

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayMark &&
          other.subjectId == subjectId &&
          other.percentageBp == percentageBp;

  @override
  int get hashCode =>
      subjectId.hashCode + (percentageBp == null ? 0 : percentageBp.hashCode);

  factory PathwayMark.fromJson(Map<String, dynamic> json) =>
      _$PathwayMarkFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayMarkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
