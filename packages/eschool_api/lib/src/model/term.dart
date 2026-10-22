//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'term.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Term {
  /// Returns a new [Term] instance.
  Term({
    required this.id,

    required this.name,

    required this.startDate,

    required this.endDate,

    required this.current,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'start_date', required: true, includeIfNull: true)
  final DateTime? startDate;

  @JsonKey(name: r'end_date', required: true, includeIfNull: true)
  final DateTime? endDate;

  /// Whether today, in the school's timezone, falls in this term.
  @JsonKey(name: r'current', required: true, includeIfNull: false)
  final bool current;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Term &&
          other.id == id &&
          other.name == name &&
          other.startDate == startDate &&
          other.endDate == endDate &&
          other.current == current;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (startDate == null ? 0 : startDate.hashCode) +
      (endDate == null ? 0 : endDate.hashCode) +
      current.hashCode;

  factory Term.fromJson(Map<String, dynamic> json) => _$TermFromJson(json);

  Map<String, dynamic> toJson() => _$TermToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
