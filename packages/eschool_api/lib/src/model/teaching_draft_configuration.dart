//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_group_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftConfiguration {
  /// Returns a new [TeachingDraftConfiguration] instance.
  TeachingDraftConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.today,

    required this.writable,

    required this.groups,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final DateTime today;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<TeachingGroupChoice> groups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.today == today &&
          other.writable == writable &&
          other.groups == groups;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      today.hashCode +
      writable.hashCode +
      groups.hashCode;

  factory TeachingDraftConfiguration.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
