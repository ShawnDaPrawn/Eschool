//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_bell_period_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicBellPeriodWrite {
  /// Returns a new [AcademicBellPeriodWrite] instance.
  AcademicBellPeriodWrite({
    required this.name,

    required this.startsAt,

    required this.endsAt,

    required this.isBreak,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'is_break', required: true, includeIfNull: false)
  final bool isBreak;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicBellPeriodWrite &&
          other.name == name &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.isBreak == isBreak;

  @override
  int get hashCode =>
      name.hashCode + startsAt.hashCode + endsAt.hashCode + isBreak.hashCode;

  factory AcademicBellPeriodWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicBellPeriodWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicBellPeriodWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
