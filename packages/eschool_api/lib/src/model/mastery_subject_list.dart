//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_subject_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_subject_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasterySubjectList {
  /// Returns a new [MasterySubjectList] instance.
  MasterySubjectList({required this.learnerId, required this.subjects});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<MasterySubjectChoice> subjects;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasterySubjectList &&
          other.learnerId == learnerId &&
          other.subjects == subjects;

  @override
  int get hashCode => learnerId.hashCode + subjects.hashCode;

  factory MasterySubjectList.fromJson(Map<String, dynamic> json) =>
      _$MasterySubjectListFromJson(json);

  Map<String, dynamic> toJson() => _$MasterySubjectListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
