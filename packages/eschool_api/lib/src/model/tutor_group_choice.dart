//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_source_choice.dart';
import 'package:eschool_api/src/model/tutor_topic_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorGroupChoice {
  /// Returns a new [TutorGroupChoice] instance.
  TutorGroupChoice({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.subjectName,

    required this.sources,

    this.topics,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: true)
  final String? subjectName;

  @JsonKey(name: r'sources', required: true, includeIfNull: false)
  final List<TutorSourceChoice> sources;

  @JsonKey(name: r'topics', required: false, includeIfNull: false)
  final List<TutorTopicChoice>? topics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.sources == sources &&
          other.topics == topics;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      (subjectName == null ? 0 : subjectName.hashCode) +
      sources.hashCode +
      topics.hashCode;

  factory TutorGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$TutorGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TutorGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
