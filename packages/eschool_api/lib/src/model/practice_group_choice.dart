//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeGroupChoice {
  /// Returns a new [PracticeGroupChoice] instance.
  PracticeGroupChoice({
    required this.id,

    required this.name,

    required this.subjectName,

    this.setId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 1
  @JsonKey(name: r'set_id', required: false, includeIfNull: false)
  final int? setId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.subjectName == subjectName &&
          other.setId == setId;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectName.hashCode +
      (setId == null ? 0 : setId.hashCode);

  factory PracticeGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$PracticeGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
