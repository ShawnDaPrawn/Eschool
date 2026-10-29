//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_deleted.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryDeleted {
  /// Returns a new [DiaryDeleted] instance.
  DiaryDeleted({required this.id});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is DiaryDeleted && other.id == id;

  @override
  int get hashCode => id.hashCode;

  factory DiaryDeleted.fromJson(Map<String, dynamic> json) =>
      _$DiaryDeletedFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryDeletedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
