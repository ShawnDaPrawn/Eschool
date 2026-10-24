//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_mark_row.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_mark_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMarkWrite {
  /// Returns a new [AcademicMarkWrite] instance.
  AcademicMarkWrite({required this.rows});

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicMarkRow> rows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMarkWrite && other.rows == rows;

  @override
  int get hashCode => rows.hashCode;

  factory AcademicMarkWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicMarkWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMarkWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
