//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/term.dart';
import 'package:json_annotation/json_annotation.dart';

part 'session_year.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SessionYear {
  /// Returns a new [SessionYear] instance.
  SessionYear({
    required this.id,

    required this.name,

    required this.startDate,

    required this.endDate,

    required this.terms,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'start_date', required: true, includeIfNull: true)
  final DateTime? startDate;

  @JsonKey(name: r'end_date', required: true, includeIfNull: true)
  final DateTime? endDate;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<Term> terms;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SessionYear &&
          other.id == id &&
          other.name == name &&
          other.startDate == startDate &&
          other.endDate == endDate &&
          other.terms == terms;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (startDate == null ? 0 : startDate.hashCode) +
      (endDate == null ? 0 : endDate.hashCode) +
      terms.hashCode;

  factory SessionYear.fromJson(Map<String, dynamic> json) =>
      _$SessionYearFromJson(json);

  Map<String, dynamic> toJson() => _$SessionYearToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
