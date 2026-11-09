//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_fixture.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_learner_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureLearnerList {
  /// Returns a new [FixtureLearnerList] instance.
  FixtureLearnerList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<LearnerFixture> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureLearnerList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory FixtureLearnerList.fromJson(Map<String, dynamic> json) =>
      _$FixtureLearnerListFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureLearnerListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
