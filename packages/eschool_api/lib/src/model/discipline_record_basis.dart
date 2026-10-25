//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_definition.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_basis.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordBasis {
  /// Returns a new [DisciplineRecordBasis] instance.
  DisciplineRecordBasis({
    required this.definition,

    required this.infractionName,

    required this.windowStart,

    required this.occurrenceCount,
  });

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final DisciplineDefinition definition;

  @JsonKey(name: r'infraction_name', required: true, includeIfNull: false)
  final String infractionName;

  @JsonKey(name: r'window_start', required: true, includeIfNull: false)
  final String windowStart;

  // minimum: 1
  @JsonKey(name: r'occurrence_count', required: true, includeIfNull: false)
  final int occurrenceCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordBasis &&
          other.definition == definition &&
          other.infractionName == infractionName &&
          other.windowStart == windowStart &&
          other.occurrenceCount == occurrenceCount;

  @override
  int get hashCode =>
      definition.hashCode +
      infractionName.hashCode +
      windowStart.hashCode +
      occurrenceCount.hashCode;

  factory DisciplineRecordBasis.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordBasisFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordBasisToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
