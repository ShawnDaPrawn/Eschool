//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_side_selections_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSideSelectionsInner {
  /// Returns a new [FixtureSideSelectionsInner] instance.
  FixtureSideSelectionsInner({
    required this.learnerId,

    required this.learnerName,

    required this.sheetLabel,

    required this.position,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'sheet_label', required: true, includeIfNull: false)
  final String sheetLabel;

  @JsonKey(name: r'position', required: true, includeIfNull: true)
  final String? position;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSideSelectionsInner &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.sheetLabel == sheetLabel &&
          other.position == position;

  @override
  int get hashCode =>
      learnerId.hashCode +
      learnerName.hashCode +
      sheetLabel.hashCode +
      (position == null ? 0 : position.hashCode);

  factory FixtureSideSelectionsInner.fromJson(Map<String, dynamic> json) =>
      _$FixtureSideSelectionsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSideSelectionsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
