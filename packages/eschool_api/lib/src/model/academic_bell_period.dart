//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_bell_period.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicBellPeriod {
  /// Returns a new [AcademicBellPeriod] instance.
  AcademicBellPeriod({
    required this.name,

    required this.startsAt,

    required this.endsAt,

    required this.isBreak,

    required this.id,

    required this.position,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'is_break', required: true, includeIfNull: false)
  final bool isBreak;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'position', required: true, includeIfNull: false)
  final int position;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicBellPeriod &&
          other.name == name &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.isBreak == isBreak &&
          other.id == id &&
          other.position == position;

  @override
  int get hashCode =>
      name.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      isBreak.hashCode +
      id.hashCode +
      position.hashCode;

  factory AcademicBellPeriod.fromJson(Map<String, dynamic> json) =>
      _$AcademicBellPeriodFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicBellPeriodToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
