//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_child.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestChild {
  /// Returns a new [WeeklyDigestChild] instance.
  WeeklyDigestChild({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestChild && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory WeeklyDigestChild.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestChildFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestChildToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
