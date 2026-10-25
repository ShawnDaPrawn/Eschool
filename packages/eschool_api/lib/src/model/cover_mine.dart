//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_occurrence.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_mine.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverMine {
  /// Returns a new [CoverMine] instance.
  CoverMine({required this.occurrences});

  @JsonKey(name: r'occurrences', required: true, includeIfNull: false)
  final List<CoverOccurrence> occurrences;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverMine && other.occurrences == occurrences;

  @override
  int get hashCode => occurrences.hashCode;

  factory CoverMine.fromJson(Map<String, dynamic> json) =>
      _$CoverMineFromJson(json);

  Map<String, dynamic> toJson() => _$CoverMineToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
