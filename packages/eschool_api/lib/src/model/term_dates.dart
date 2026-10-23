//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'term_dates.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermDates {
  /// Returns a new [TermDates] instance.
  TermDates({required this.name, required this.startsOn, required this.endsOn});

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: true)
  final DateTime? startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: true)
  final DateTime? endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermDates &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      name.hashCode +
      (startsOn == null ? 0 : startsOn.hashCode) +
      (endsOn == null ? 0 : endsOn.hashCode);

  factory TermDates.fromJson(Map<String, dynamic> json) =>
      _$TermDatesFromJson(json);

  Map<String, dynamic> toJson() => _$TermDatesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
