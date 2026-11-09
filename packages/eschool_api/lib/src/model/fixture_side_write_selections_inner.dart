//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_side_write_selections_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSideWriteSelectionsInner {
  /// Returns a new [FixtureSideWriteSelectionsInner] instance.
  FixtureSideWriteSelectionsInner({
    required this.learnerId,

    this.sheetLabel,

    this.position,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'sheet_label', required: false, includeIfNull: false)
  final String? sheetLabel;

  @JsonKey(name: r'position', required: false, includeIfNull: false)
  final String? position;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSideWriteSelectionsInner &&
          other.learnerId == learnerId &&
          other.sheetLabel == sheetLabel &&
          other.position == position;

  @override
  int get hashCode =>
      learnerId.hashCode +
      (sheetLabel == null ? 0 : sheetLabel.hashCode) +
      (position == null ? 0 : position.hashCode);

  factory FixtureSideWriteSelectionsInner.fromJson(Map<String, dynamic> json) =>
      _$FixtureSideWriteSelectionsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$FixtureSideWriteSelectionsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
