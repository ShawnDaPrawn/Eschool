//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_lesson.dart';
import 'package:eschool_api/src/model/learning_path_teach_tutor.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_teach.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathTeach {
  /// Returns a new [LearningPathTeach] instance.
  LearningPathTeach({required this.lessons, required this.tutor});

  @JsonKey(name: r'lessons', required: true, includeIfNull: false)
  final List<LearningPathLesson> lessons;

  @JsonKey(name: r'tutor', required: true, includeIfNull: false)
  final LearningPathTeachTutor tutor;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathTeach &&
          other.lessons == lessons &&
          other.tutor == tutor;

  @override
  int get hashCode => lessons.hashCode + tutor.hashCode;

  factory LearningPathTeach.fromJson(Map<String, dynamic> json) =>
      _$LearningPathTeachFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathTeachToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
