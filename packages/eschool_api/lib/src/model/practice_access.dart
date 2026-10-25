//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_group_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeAccess {
  /// Returns a new [PracticeAccess] instance.
  PracticeAccess({
    required this.readerId,

    required this.learnerId,

    required this.practiceOn,

    required this.writable,

    required this.staffGroups,

    required this.learnerGroups,
  });

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  @JsonKey(name: r'practice_on', required: true, includeIfNull: false)
  final String practiceOn;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'staff_groups', required: true, includeIfNull: false)
  final List<PracticeGroupChoice> staffGroups;

  @JsonKey(name: r'learner_groups', required: true, includeIfNull: false)
  final List<PracticeGroupChoice> learnerGroups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeAccess &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.practiceOn == practiceOn &&
          other.writable == writable &&
          other.staffGroups == staffGroups &&
          other.learnerGroups == learnerGroups;

  @override
  int get hashCode =>
      readerId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      practiceOn.hashCode +
      writable.hashCode +
      staffGroups.hashCode +
      learnerGroups.hashCode;

  factory PracticeAccess.fromJson(Map<String, dynamic> json) =>
      _$PracticeAccessFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
