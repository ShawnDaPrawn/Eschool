//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/guardian_invitation_preview_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_school_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSchoolList {
  /// Returns a new [FixtureSchoolList] instance.
  FixtureSchoolList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GuardianInvitationPreviewLearnersInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSchoolList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory FixtureSchoolList.fromJson(Map<String, dynamic> json) =>
      _$FixtureSchoolListFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSchoolListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
