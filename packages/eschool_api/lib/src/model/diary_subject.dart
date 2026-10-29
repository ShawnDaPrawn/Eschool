//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_subject.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiarySubject {
  /// Returns a new [DiarySubject] instance.
  DiarySubject({required this.id, required this.name});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiarySubject && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory DiarySubject.fromJson(Map<String, dynamic> json) =>
      _$DiarySubjectFromJson(json);

  Map<String, dynamic> toJson() => _$DiarySubjectToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
