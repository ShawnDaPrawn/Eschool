//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_sheet_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSheetEntry {
  /// Returns a new [FixtureSheetEntry] instance.
  FixtureSheetEntry({required this.sheetLabel, required this.position});

  @JsonKey(name: r'sheet_label', required: true, includeIfNull: false)
  final String sheetLabel;

  @JsonKey(name: r'position', required: true, includeIfNull: true)
  final String? position;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSheetEntry &&
          other.sheetLabel == sheetLabel &&
          other.position == position;

  @override
  int get hashCode =>
      sheetLabel.hashCode + (position == null ? 0 : position.hashCode);

  factory FixtureSheetEntry.fromJson(Map<String, dynamic> json) =>
      _$FixtureSheetEntryFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSheetEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
