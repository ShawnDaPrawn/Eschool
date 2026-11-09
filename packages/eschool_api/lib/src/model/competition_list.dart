//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/competition_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'competition_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionList {
  /// Returns a new [CompetitionList] instance.
  CompetitionList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CompetitionSummary> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory CompetitionList.fromJson(Map<String, dynamic> json) =>
      _$CompetitionListFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
